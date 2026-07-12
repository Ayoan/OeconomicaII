import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', 'formatters'))
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from formatters.format_rakuten_csv import format_rakuten_csv  # noqa: E402

HEADER = '利用日,利用店名・商品名,利用者,支払方法,利用金額,支払手数料,支払総額,今回支払金額,支払残高,新規サイン,督促\n'


def _write_input_csv(datas_dir, rows_text):
    with open(os.path.join(datas_dir, 'enavi202607(1234).csv'), 'w', encoding='utf-8-sig', newline='') as f:
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
