import os
import sys
from unittest.mock import patch

import pytest

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', 'formatters'))
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from email_fetcher.imap_client import EmailFetchError  # noqa: E402
from formatters.format_rakuten_email import format_rakuten_email, parse_email_body  # noqa: E402

MULTI_TRANSACTION_BODY = """━━━━━━━━━━
カード利用お知らせメール
━━━━━━━━━━

楽天カード（Visa）をご利用いただき誠にありがとうございます。
楽天カードご利用内容をお知らせいたします。

<カードご利用情報>
《ショッピングご利用分》
■利用日: 2026/07/10
■利用先: ｸﾗｽ
■利用者: 本人
■支払方法: 1回
■利用金額: 997 円
■支払月: 2026/08

■利用日: 2026/07/10
■利用先: AMAZON DOWNLOADS
■利用者: 本人
■支払方法: 1回
■利用金額: 590 円
■支払月: 2026/08

■利用日: 2026/07/11
■利用先: ｺﾞｰﾙﾄﾞｼﾞﾑ
■利用者: 本人
■支払方法: 1回
■利用金額: 17,451 円
■支払月: 2026/08

■ご利用明細のご確認
https://www.rakuten-card.co.jp/e-navi/members/statement/index.xhtml
"""

SINGLE_TRANSACTION_BODY = """■利用日: 2026/07/15
■利用先: ＫＹＯＴＯ　ＣＯ−ＯＰ
■利用者: 本人
■支払方法: 1回
■利用金額: 2,056 円
■支払月: 2026/08
"""


def test_parse_email_body_extracts_all_transactions_in_one_email():
    records = parse_email_body(MULTI_TRANSACTION_BODY)

    assert len(records) == 3
    assert records[0] == {
        '日付': '2026-07-10',
        '収支区分': '支出',
        'カテゴリ': '',
        '金額': 997,
        'メモ': 'クラス',
    }
    assert records[1]['メモ'] == 'AMAZON DOWNLOADS'
    assert records[1]['金額'] == 590
    assert records[2]['日付'] == '2026-07-11'
    assert records[2]['金額'] == 17451


def test_parse_email_body_converts_zenkaku_place_to_hankaku():
    records = parse_email_body(SINGLE_TRANSACTION_BODY)

    assert len(records) == 1
    assert records[0]['メモ'] == 'KYOTO CO-OP'
    assert records[0]['金額'] == 2056


def test_parse_email_body_converts_hankaku_kana_to_zenkaku():
    """半角カナの店舗名(ISO-2022-JPメール由来)は、他の登録経路の全角カナ表記
    とフォントを統一するため全角カナへ変換すること"""
    body = """■利用日: 2026/07/11
■利用先: ｺﾞｰﾙﾄﾞｼﾞﾑ
■利用者: 本人
■支払方法: 1回
■利用金額: 17,451 円
■支払月: 2026/08
"""
    records = parse_email_body(body)

    assert records[0]['メモ'] == 'ゴールドジム'


def test_parse_email_body_returns_empty_list_when_no_transactions():
    assert parse_email_body('無関係なメール本文です') == []


def test_parse_email_body_skips_non_numeric_amount():
    body = SINGLE_TRANSACTION_BODY.replace('2,056 円', '確認中')
    assert parse_email_body(body) == []


@patch('formatters.format_rakuten_email.fetch_matching_emails')
def test_format_rakuten_email_writes_csv_for_all_transactions(mock_fetch, tmp_path, monkeypatch):
    mock_fetch.return_value = [MULTI_TRANSACTION_BODY, SINGLE_TRANSACTION_BODY]

    monkeypatch.setattr('formatters.format_rakuten_email.get_script_directory', lambda: str(tmp_path))
    monkeypatch.setattr('formatters.format_rakuten_email.get_today_date_string', lambda: '20260719')
    (tmp_path / 'datas').mkdir()

    output_file = format_rakuten_email({'IMAP_HOST': 'x', 'EMAIL_ADDRESS': 'x', 'APP_PASSWORD': 'x',
                                          'SENDER_FILTER': 'x'})

    assert output_file == str(tmp_path / 'datas' / 'rakuten_email_20260719_formatted.csv')
    content = open(output_file, encoding='utf-8').read()
    assert content.count('\n') == 5  # header + 4 records (3 + 1)
    assert 'KYOTO CO-OP' in content


@patch('formatters.format_rakuten_email.fetch_matching_emails')
def test_format_rakuten_email_returns_none_when_no_matching_emails(mock_fetch):
    mock_fetch.return_value = []

    result = format_rakuten_email({'IMAP_HOST': 'x', 'EMAIL_ADDRESS': 'x', 'APP_PASSWORD': 'x',
                                     'SENDER_FILTER': 'x'})

    assert result is None


@patch('formatters.format_rakuten_email.fetch_matching_emails')
def test_format_rakuten_email_propagates_fetch_error(mock_fetch):
    mock_fetch.side_effect = EmailFetchError('IMAP接続に失敗しました')

    with pytest.raises(EmailFetchError):
        format_rakuten_email({'IMAP_HOST': 'x', 'EMAIL_ADDRESS': 'x', 'APP_PASSWORD': 'x',
                               'SENDER_FILTER': 'x'})
