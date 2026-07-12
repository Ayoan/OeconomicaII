import os
import sys
import time

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', 'formatters'))
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from formatters.format_rakuten_csv import format_rakuten_csv  # noqa: E402

HEADER = '利用日,利用店名・商品名,利用者,支払方法,利用金額,支払手数料,支払総額,今回支払金額,支払残高,新規サイン,督促\n'


def _write_input_csv(datas_dir, rows_text, filename='enavi202607(1234).csv'):
    with open(os.path.join(datas_dir, filename), 'w', encoding='utf-8-sig', newline='') as f:
        f.write(HEADER)
        f.write(rows_text)


def test_skips_foreign_currency_detail_row_with_empty_date(tmp_path, monkeypatch):
    """外貨決済の明細行(利用日・利用金額が空欄で、利用店名・商品名に
    為替レート補足が入る行)は日付が無いのでスキップし、int変換エラーで
    パイプライン全体が落ちないこと(2026-07-12、本番環境で実際に発生した不具合)"""
    datas_dir = tmp_path / 'datas'
    datas_dir.mkdir()
    _write_input_csv(datas_dir, (
        '2026/06/28,ANTHROPIC* CLAUDE SUBSCRIPTION,本人,1回払い,36913,0,36913,36913,36913,0,*\n'
        ',ご利用金額 220.000ドル（レート 167.787円）,,,,,,,,,\n'
        '2026/06/27,AMAZON.CO.JP,本人,1回払い,555,0,555,555,555,0,*\n'
    ))

    monkeypatch.setattr('formatters.format_rakuten_csv.get_script_directory', lambda: str(tmp_path))
    monkeypatch.setattr('formatters.format_rakuten_csv.get_today_date_string', lambda: '20260712')

    format_rakuten_csv()

    output_path = datas_dir / 'enavi_20260712_formatted.csv'
    content = output_path.read_text(encoding='utf-8')

    assert 'ANTHROPIC' in content
    assert 'AMAZON.CO.JP' in content
    assert content.count('\n') == 3  # header + 2 records


def test_archives_raw_csv_after_formatting(tmp_path, monkeypatch):
    """処理済みの生CSVはformatters/datas/直下に残さずprocessed/へ退避すること
    (2026-07-12、ブラウザの重複ダウンロードでenavi(1).csv等が溜まり続け、
    次回実行時に古いファイルを誤って処理する不具合が本番環境で発生したため)"""
    datas_dir = tmp_path / 'datas'
    datas_dir.mkdir()
    _write_input_csv(datas_dir, '2026/06/27,AMAZON.CO.JP,本人,1回払い,555,0,555,555,555,0,*\n')

    monkeypatch.setattr('formatters.format_rakuten_csv.get_script_directory', lambda: str(tmp_path))
    monkeypatch.setattr('formatters.format_rakuten_csv.get_today_date_string', lambda: '20260712')

    format_rakuten_csv()

    assert not (datas_dir / 'enavi202607(1234).csv').exists()
    processed = list((datas_dir / 'processed').glob('enavi202607(1234)_*.csv'))
    assert len(processed) == 1


def test_uses_newest_file_when_multiple_raw_csv_exist(tmp_path, monkeypatch):
    """複数の生CSV(重複ダウンロード連番等)が存在する場合、最終更新が最新の
    ファイルを処理対象にすること。古い方には無関係なデータが入っているため、
    誤って古い方が選ばれると正しくない金額で登録されてしまう"""
    datas_dir = tmp_path / 'datas'
    datas_dir.mkdir()
    _write_input_csv(datas_dir, '2026/06/01,OLD DATA,本人,1回払い,111,0,111,111,111,0,*\n',
                      filename='enavi202607(1234).csv')
    time.sleep(0.01)
    _write_input_csv(datas_dir, '2026/06/27,NEW DATA,本人,1回払い,555,0,555,555,555,0,*\n',
                      filename='enavi202607(1234) (1).csv')

    monkeypatch.setattr('formatters.format_rakuten_csv.get_script_directory', lambda: str(tmp_path))
    monkeypatch.setattr('formatters.format_rakuten_csv.get_today_date_string', lambda: '20260712')

    format_rakuten_csv()

    content = (datas_dir / 'enavi_20260712_formatted.csv').read_text(encoding='utf-8')
    assert 'NEW DATA' in content
    assert 'OLD DATA' not in content
    # 使わなかった方も含め、両方の生CSVが退避されること
    assert list((datas_dir / 'processed').glob('*.csv')).__len__() == 2
