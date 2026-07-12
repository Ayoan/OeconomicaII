import os
import sys
from unittest.mock import patch

import pytest

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', 'formatters'))
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from email_fetcher.imap_client import EmailFetchError  # noqa: E402
from formatters.format_smbc_email import format_smbc_email, parse_email_body  # noqa: E402

SAMPLE_BODY = """しろやぎさん　様

いつも三井住友カードをご利用頂きありがとうございます。
お客様のカードご利用内容をお知らせいたします。'

ご利用カード：三井住友ゴールドＶＩＳＡ（ＮＬ）

◇利用日：2026/07/05 10:25
◇利用先：BURIAN KITAYAMATEN
◇利用取引：買物
◇利用金額：3,300円
"""

FAMILY_CARD_BODY = """しろやぎさん　様

ご家族会員さまのカードご利用内容をお知らせいたします。'

◇利用日：2026/07/05 12:20
◇利用先：ＫＹＯＴＯ　ＣＯ−ＯＰ
◇利用取引：買物
◇利用金額：2,056円
"""


def test_parse_email_body_extracts_date_place_amount():
    record = parse_email_body(SAMPLE_BODY)

    assert record == {
        '日付': '2026-07-05',
        '収支区分': '支出',
        'カテゴリ': '',
        '金額': 3300,
        'メモ': 'BURIAN KITAYAMATEN',
    }


def test_parse_email_body_converts_zenkaku_place_to_hankaku():
    record = parse_email_body(FAMILY_CARD_BODY)

    assert record['メモ'] == 'KYOTO CO-OP'
    assert record['金額'] == 2056


def test_parse_email_body_returns_none_when_fields_missing():
    assert parse_email_body('無関係なメール本文です') is None


def test_parse_email_body_returns_none_for_non_numeric_amount():
    body = SAMPLE_BODY.replace('3,300円', '確認中')
    assert parse_email_body(body) is None


@patch('formatters.format_smbc_email.fetch_matching_emails')
def test_format_smbc_email_writes_csv_for_matching_bodies(mock_fetch, tmp_path, monkeypatch):
    mock_fetch.return_value = [SAMPLE_BODY, FAMILY_CARD_BODY]

    monkeypatch.setattr('formatters.format_smbc_email.get_script_directory', lambda: str(tmp_path))
    monkeypatch.setattr('formatters.format_smbc_email.get_today_date_string', lambda: '20260712')
    (tmp_path / 'datas').mkdir()

    output_file = format_smbc_email({'IMAP_HOST': 'x', 'EMAIL_ADDRESS': 'x', 'APP_PASSWORD': 'x',
                                       'SENDER_FILTER': 'x'})

    assert output_file == str(tmp_path / 'datas' / 'smbc_email_20260712_formatted.csv')
    content = open(output_file, encoding='utf-8').read()
    assert 'BURIAN KITAYAMATEN' in content
    assert 'KYOTO CO-OP' in content


@patch('formatters.format_smbc_email.fetch_matching_emails')
def test_format_smbc_email_returns_none_when_no_matching_emails(mock_fetch):
    mock_fetch.return_value = []

    result = format_smbc_email({'IMAP_HOST': 'x', 'EMAIL_ADDRESS': 'x', 'APP_PASSWORD': 'x',
                                  'SENDER_FILTER': 'x'})

    assert result is None


@patch('formatters.format_smbc_email.fetch_matching_emails')
def test_format_smbc_email_skips_unparseable_bodies(mock_fetch, tmp_path, monkeypatch):
    mock_fetch.return_value = [SAMPLE_BODY, '無関係なメール本文です']

    monkeypatch.setattr('formatters.format_smbc_email.get_script_directory', lambda: str(tmp_path))
    monkeypatch.setattr('formatters.format_smbc_email.get_today_date_string', lambda: '20260712')
    (tmp_path / 'datas').mkdir()

    output_file = format_smbc_email({'IMAP_HOST': 'x', 'EMAIL_ADDRESS': 'x', 'APP_PASSWORD': 'x',
                                       'SENDER_FILTER': 'x'})

    content = open(output_file, encoding='utf-8').read()
    assert content.count('\n') == 2  # header + 1 record


@patch('formatters.format_smbc_email.fetch_matching_emails')
def test_format_smbc_email_propagates_fetch_error(mock_fetch):
    """IMAP接続/認証失敗時は呼び出し側(main.py)でスクレイピング失敗時と同様に
    LINE通知できるよう、もみ消さずに送出すること"""
    mock_fetch.side_effect = EmailFetchError('IMAP接続に失敗しました')

    with pytest.raises(EmailFetchError):
        format_smbc_email({'IMAP_HOST': 'x', 'EMAIL_ADDRESS': 'x', 'APP_PASSWORD': 'x',
                            'SENDER_FILTER': 'x'})
