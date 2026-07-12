import csv
import json
import os
import sys
from unittest.mock import patch

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from main import archive_processed_csv, main, notify_summary, register_records, run_pipeline  # noqa: E402

FIELDNAMES = ['日付', '収支区分', 'カテゴリ', '金額', 'メモ']


def _write_formatted_csv(path, rows):
    with open(path, 'w', encoding='utf-8', newline='') as f:
        writer = csv.DictWriter(f, fieldnames=FIELDNAMES)
        writer.writeheader()
        writer.writerows(rows)


def _write_config(path, lookback_days=14):
    with open(path, 'w', encoding='utf-8') as f:
        json.dump({'DEDUP_CONFIG': {'LOOKBACK_DAYS': lookback_days}}, f)


def _write_mapping(path):
    with open(path, 'w', encoding='utf-8') as f:
        json.dump({
            'text_contains_mappings': [{'keyword': 'セブン-イレブン', 'category': '日用品'}],
            'default_category': '不明',
        }, f)


def test_run_pipeline_excludes_db_duplicates_and_fills_category(tmp_path):
    csv_path = tmp_path / 'smbccard_20260710_formatted.csv'
    _write_formatted_csv(csv_path, [
        {'日付': '2026-07-10', '収支区分': '支出', 'カテゴリ': '', '金額': '300', 'メモ': 'セブン-イレブン 渋谷店'},
        {'日付': '2026-07-11', '収支区分': '支出', 'カテゴリ': '', '金額': '1200', 'メモ': '謎の店舗'},
    ])
    config_path = tmp_path / 'config.json'
    _write_config(config_path)
    mapping_path = tmp_path / 'category_mapping.json'
    _write_mapping(mapping_path)

    # DB側には1件目(300円/セブン-イレブン)が既に登録済みという想定
    def fake_fetch_existing(user_id, start_date, end_date):
        return [{'date': '2026-07-10', 'balance': 'expense', 'category': '日用品', 'amount': 300, 'memo': 'セブン-イレブン 渋谷店'}]

    to_insert = run_pipeline(
        user_id=1,
        csv_paths=[str(csv_path)],
        config_path=str(config_path),
        mapping_path=str(mapping_path),
        fetch_existing=fake_fetch_existing,
    )

    assert len(to_insert) == 1
    assert to_insert[0]['メモ'] == '謎の店舗'
    assert to_insert[0]['カテゴリ'] == '不明'


def test_run_pipeline_returns_empty_list_when_no_records(tmp_path):
    csv_path = tmp_path / 'empty_formatted.csv'
    _write_formatted_csv(csv_path, [])
    config_path = tmp_path / 'config.json'
    _write_config(config_path)
    mapping_path = tmp_path / 'category_mapping.json'
    _write_mapping(mapping_path)

    called = []

    def fake_fetch_existing(user_id, start_date, end_date):
        called.append(True)
        return []

    to_insert = run_pipeline(
        user_id=1,
        csv_paths=[str(csv_path)],
        config_path=str(config_path),
        mapping_path=str(mapping_path),
        fetch_existing=fake_fetch_existing,
    )

    assert to_insert == []
    assert called == []  # レコードが無ければDB照会自体を行わない


def test_register_records_returns_zero_when_no_records():
    result = register_records(1, [])
    assert result == {'inserted': 0, 'errors': []}


@patch('main.insert_records')
@patch('main.get_categories')
@patch('main.ensure_category_exists')
def test_register_records_ensures_category_then_validates_and_inserts(mock_ensure, mock_get_categories, mock_insert):
    mock_get_categories.return_value = {'income': [], 'expense': ['不明']}
    mock_insert.return_value = 1

    to_insert = [{'日付': '2026-07-11', '収支区分': '支出', 'カテゴリ': '不明', '金額': 1200, 'メモ': '謎の店舗'}]
    result = register_records(1, to_insert)

    mock_ensure.assert_called_once_with(1, '不明', 'expense')
    mock_insert.assert_called_once_with(1, to_insert)
    assert result == {'inserted': 1, 'errors': []}


@patch('main.insert_records')
@patch('main.get_categories')
@patch('main.ensure_category_exists')
def test_register_records_excludes_invalid_records_from_insert(mock_ensure, mock_get_categories, mock_insert):
    mock_get_categories.return_value = {'income': [], 'expense': ['不明']}
    mock_insert.return_value = 1

    to_insert = [
        {'日付': '2026-07-11', '収支区分': '支出', 'カテゴリ': '不明', '金額': 1200, 'メモ': 'OK'},
        {'日付': '2026-07-11', '収支区分': '支出', 'カテゴリ': '不明', '金額': 0, 'メモ': 'NG: 金額0'},
    ]
    result = register_records(1, to_insert)

    inserted_records = mock_insert.call_args.args[1]
    assert len(inserted_records) == 1
    assert result['inserted'] == 1
    assert len(result['errors']) == 1


@patch('main.send_line_message')
def test_notify_summary_skips_when_nothing_happened(mock_send):
    notify_summary({}, inserted=0, errors=[], skipped_sources=[])
    mock_send.assert_not_called()


@patch('main.send_line_message')
def test_notify_summary_sends_when_records_inserted(mock_send):
    notify_summary({}, inserted=3, errors=[], skipped_sources=[])
    mock_send.assert_called_once()


@patch('main.send_line_message')
def test_notify_summary_sends_when_errors_present_even_if_zero_inserted(mock_send):
    notify_summary({}, inserted=0, errors=['カテゴリ不正'], skipped_sources=[])
    mock_send.assert_called_once()


@patch('main.send_line_message')
def test_notify_summary_sends_when_sources_skipped_even_if_zero_inserted(mock_send):
    notify_summary({}, inserted=0, errors=[], skipped_sources=['e-navi'])
    mock_send.assert_called_once()


@patch('main.send_line_message')
def test_notify_summary_does_not_raise_when_send_fails(mock_send):
    mock_send.side_effect = RuntimeError('network error')
    notify_summary({}, inserted=1, errors=[], skipped_sources=[])  # 例外が伝播しないこと


@patch('formatters.format_rakuten_csv.format_rakuten_csv')
@patch('scrapers.run_scrapers.run_all_scrapers')
def test_main_scrape_formats_enavi_csv_after_successful_scraping(mock_run_scrapers, mock_format_rakuten, tmp_path, monkeypatch):
    """--scrapeでe-naviの取得に成功した場合、ダウンロードしたCSVを整形CSVへ
    変換するformat_rakuten_csv()が自動で呼ばれること
    (ダウンロードして終わりでは main.py が拾う *_formatted.csv が生成されない不具合の再発防止)"""
    mock_run_scrapers.return_value = []  # 失敗データソース無し = e-navi成功

    config_path = tmp_path / 'config.json'
    config_path.write_text(json.dumps({'CREDIT_CARDS': {'E_NAVI': {'LOGIN_URL': 'https://example.com'}}}))

    monkeypatch.setattr(sys, 'argv', ['main.py', '--user-id', '1', '--scrape', '--config', str(config_path)])
    monkeypatch.setattr('main.glob.glob', lambda pattern: [])
    monkeypatch.setattr('main.notify_summary', lambda *a, **k: None)

    main()

    mock_format_rakuten.assert_called_once()


def test_archive_processed_csv_moves_files_with_user_id_suffix(tmp_path):
    processed_dir = tmp_path / 'processed'
    csv_path = tmp_path / 'enavi_20260712_formatted.csv'
    csv_path.write_text('日付,収支区分,カテゴリ,金額,メモ\n')

    archive_processed_csv([str(csv_path)], user_id=2, processed_dir=str(processed_dir))

    assert not csv_path.exists()
    moved = list(processed_dir.glob('enavi_20260712_formatted_user2_*.csv'))
    assert len(moved) == 1


def test_archive_processed_csv_ignores_missing_files(tmp_path):
    processed_dir = tmp_path / 'processed'

    archive_processed_csv([str(tmp_path / 'does_not_exist.csv')], user_id=2, processed_dir=str(processed_dir))
    # 例外が伝播しないこと


@patch('main.insert_records')
@patch('main.get_categories')
@patch('main.ensure_category_exists')
@patch('main.archive_processed_csv')
@patch('main.fetch_existing_records')
def test_main_archives_csv_found_via_default_glob(mock_fetch, mock_archive, mock_ensure, mock_get_categories,
                                                    mock_insert, tmp_path, monkeypatch):
    mock_fetch.return_value = []
    mock_get_categories.return_value = {'income': [], 'expense': ['不明']}
    mock_insert.return_value = 1

    csv_path = tmp_path / 'enavi_20260712_formatted.csv'
    _write_formatted_csv(csv_path, [
        {'日付': '2026-07-05', '収支区分': '支出', 'カテゴリ': '不明', '金額': '100', 'メモ': 'テスト'},
    ])
    config_path = tmp_path / 'config.json'
    _write_config(config_path)

    monkeypatch.setattr(sys, 'argv', ['main.py', '--user-id', '2', '--config', str(config_path)])
    monkeypatch.setattr('main.glob.glob', lambda pattern: [str(csv_path)])
    monkeypatch.setattr('main.notify_summary', lambda *a, **k: None)

    main()

    mock_archive.assert_called_once_with([str(csv_path)], 2)


@patch('main.insert_records')
@patch('main.get_categories')
@patch('main.ensure_category_exists')
@patch('main.archive_processed_csv')
@patch('main.fetch_existing_records')
def test_main_archives_default_glob_csv_but_not_explicit_csv(mock_fetch, mock_archive, mock_ensure,
                                                               mock_get_categories, mock_insert,
                                                               tmp_path, monkeypatch):
    """formatters/datas/直下の自動検出分は処理後に退避されるが、--csvで明示指定
    したファイルは呼び出し側の管理下にあるため退避しないこと
    (2026-07-12、本番環境でアカウント間のデータ混同事故が発生したため導入)"""
    mock_fetch.return_value = []
    mock_get_categories.return_value = {'income': [], 'expense': ['不明']}
    mock_insert.return_value = 1

    csv_path = tmp_path / 'smbc_email_20260712_formatted.csv'
    _write_formatted_csv(csv_path, [
        {'日付': '2026-07-05', '収支区分': '支出', 'カテゴリ': '不明', '金額': '100', 'メモ': 'テスト'},
    ])
    config_path = tmp_path / 'config.json'
    _write_config(config_path)

    monkeypatch.setattr(sys, 'argv', [
        'main.py', '--user-id', '3', '--csv', str(csv_path), '--config', str(config_path),
    ])
    monkeypatch.setattr('main.notify_summary', lambda *a, **k: None)

    main()

    mock_archive.assert_not_called()


@patch('formatters.format_rakuten_csv.format_rakuten_csv')
@patch('scrapers.run_scrapers.run_all_scrapers')
def test_main_scrape_skips_formatting_when_enavi_fails(mock_run_scrapers, mock_format_rakuten, tmp_path, monkeypatch):
    mock_run_scrapers.return_value = ['e-navi']  # e-navi取得失敗

    config_path = tmp_path / 'config.json'
    config_path.write_text(json.dumps({'CREDIT_CARDS': {'E_NAVI': {'LOGIN_URL': 'https://example.com'}}}))

    monkeypatch.setattr(sys, 'argv', ['main.py', '--user-id', '1', '--scrape', '--config', str(config_path)])
    monkeypatch.setattr('main.glob.glob', lambda pattern: [])
    monkeypatch.setattr('main.notify_summary', lambda *a, **k: None)

    main()

    mock_format_rakuten.assert_not_called()
