import os
import sys
from email.header import Header
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from unittest.mock import MagicMock, patch

import pytest

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from email_fetcher.imap_client import (  # noqa: E402
    EmailFetchError,
    _decode_subject,
    _extract_plain_text,
    _strip_html_tags,
    fetch_matching_emails,
)

EMAIL_CONFIG = {
    'IMAP_HOST': 'imap.gmail.com',
    'IMAP_PORT': 993,
    'EMAIL_ADDRESS': 'user@gmail.com',
    'APP_PASSWORD': 'app-password',
    'SENDER_FILTER': 'statement@vpass.ne.jp',
    'SUBJECT_FILTER': 'ご利用のお知らせ【三井住友カード】',
    'LOOKBACK_DAYS': 7,
}


def _build_plain_message(subject, body):
    msg = MIMEText(body, 'plain', 'utf-8')
    msg['Subject'] = Header(subject, 'utf-8')
    return msg.as_bytes()


def _make_imap_mock(message_bytes_list):
    imap = MagicMock()
    imap.search.return_value = ('OK', [b' '.join(str(i).encode() for i in range(len(message_bytes_list)))])
    imap.fetch.side_effect = [
        ('OK', [(b'1 (RFC822 {123}', msg_bytes)]) for msg_bytes in message_bytes_list
    ]
    return imap


@patch('email_fetcher.imap_client.imaplib.IMAP4_SSL')
def test_fetch_matching_emails_returns_bodies_matching_subject(mock_imap_ssl_cls):
    matching = _build_plain_message('ご利用のお知らせ【三井住友カード】', 'ご利用日時：2026/07/08 17:31')
    non_matching = _build_plain_message('別件のお知らせ', '無関係な内容')

    imap = _make_imap_mock([matching, non_matching])
    mock_imap_ssl_cls.return_value = imap

    bodies = fetch_matching_emails(EMAIL_CONFIG)

    assert len(bodies) == 1
    assert 'ご利用日時' in bodies[0]
    imap.login.assert_called_once_with(EMAIL_CONFIG['EMAIL_ADDRESS'], EMAIL_CONFIG['APP_PASSWORD'])
    imap.logout.assert_called_once()


@patch('email_fetcher.imap_client.imaplib.IMAP4_SSL')
def test_fetch_matching_emails_uses_sender_and_since_in_search(mock_imap_ssl_cls):
    imap = _make_imap_mock([])
    mock_imap_ssl_cls.return_value = imap

    fetch_matching_emails(EMAIL_CONFIG)

    search_call = imap.search.call_args
    criteria = search_call.args[1]
    assert EMAIL_CONFIG['SENDER_FILTER'] in criteria
    assert 'SINCE' in criteria


@patch('email_fetcher.imap_client.imaplib.IMAP4_SSL')
def test_selects_configured_mailbox_when_set(mock_imap_ssl_cls):
    imap = _make_imap_mock([])
    mock_imap_ssl_cls.return_value = imap
    config = dict(EMAIL_CONFIG, MAILBOX='VPASS')

    fetch_matching_emails(config)

    imap.select.assert_called_once_with('VPASS')


@patch('email_fetcher.imap_client.imaplib.IMAP4_SSL')
def test_selects_inbox_by_default(mock_imap_ssl_cls):
    imap = _make_imap_mock([])
    mock_imap_ssl_cls.return_value = imap

    fetch_matching_emails(EMAIL_CONFIG)  # MAILBOX未設定

    imap.select.assert_called_once_with('INBOX')


@patch('email_fetcher.imap_client.imaplib.IMAP4_SSL')
def test_selects_nested_japanese_label_encoded_as_modified_utf7(mock_imap_ssl_cls):
    """日本語の階層ラベル（例:「クレジットカード」配下の「楽天カード」）は
    imaplibがASCIIとしてそのまま送信するため、事前にIMAP変更UTF-7へ
    エンコードしないとUnicodeEncodeErrorになる(2026-07-19、実際にラベル構造を
    「クレジットカード/VPASS」「クレジットカード/楽天カード」へ変更した際に発覚)"""
    imap = _make_imap_mock([])
    mock_imap_ssl_cls.return_value = imap
    config = dict(EMAIL_CONFIG, MAILBOX='クレジットカード/楽天カード')

    fetch_matching_emails(config)

    imap.select.assert_called_once_with('&MK8w7DC4MMMwyDCrMPwwyQ-/&aX1ZKTCrMPwwyQ-')


@patch('email_fetcher.imap_client.imaplib.IMAP4_SSL')
def test_subject_exact_match_excludes_similarly_prefixed_subject(mock_imap_ssl_cls):
    """楽天カードの「カード利用のお知らせ(本人ご利用分)」と、部分一致してしまう
    「【速報版】カード利用のお知らせ(本人ご利用分)」（詳細情報を含まない速報版）
    を区別するため、SUBJECT_EXACT_MATCH指定時は完全一致のみ通すこと"""
    exact = _build_plain_message('カード利用のお知らせ(本人ご利用分)', '確定版の本文')
    prefixed = _build_plain_message('【速報版】カード利用のお知らせ(本人ご利用分)', '速報版の本文')

    imap = _make_imap_mock([exact, prefixed])
    mock_imap_ssl_cls.return_value = imap
    config = dict(EMAIL_CONFIG, SUBJECT_FILTER='カード利用のお知らせ(本人ご利用分)', SUBJECT_EXACT_MATCH=True)

    bodies = fetch_matching_emails(config)

    assert len(bodies) == 1
    assert '確定版の本文' in bodies[0]


@patch('email_fetcher.imap_client.imaplib.IMAP4_SSL')
def test_subject_filter_without_exact_match_allows_prefixed_subject(mock_imap_ssl_cls):
    """SUBJECT_EXACT_MATCH省略時は従来通り部分一致（後方互換）"""
    prefixed = _build_plain_message('【速報版】カード利用のお知らせ(本人ご利用分)', '速報版の本文')

    imap = _make_imap_mock([prefixed])
    mock_imap_ssl_cls.return_value = imap
    config = dict(EMAIL_CONFIG, SUBJECT_FILTER='カード利用のお知らせ(本人ご利用分)')

    bodies = fetch_matching_emails(config)

    assert len(bodies) == 1


@patch('email_fetcher.imap_client.imaplib.IMAP4_SSL')
def test_login_failure_raises_email_fetch_error(mock_imap_ssl_cls):
    imap = MagicMock()
    imap.login.side_effect = Exception('auth failed')
    mock_imap_ssl_cls.return_value = imap

    with pytest.raises(EmailFetchError):
        fetch_matching_emails(EMAIL_CONFIG)


@patch('email_fetcher.imap_client.imaplib.IMAP4_SSL')
def test_logout_called_even_when_search_fails(mock_imap_ssl_cls):
    imap = MagicMock()
    imap.search.return_value = ('NO', [None])
    mock_imap_ssl_cls.return_value = imap

    with pytest.raises(EmailFetchError):
        fetch_matching_emails(EMAIL_CONFIG)

    imap.logout.assert_called_once()


def test_decode_subject_handles_mime_encoded_header():
    raw = str(Header('ご利用のお知らせ【三井住友カード】', 'utf-8'))
    assert _decode_subject(raw) == 'ご利用のお知らせ【三井住友カード】'


def test_decode_subject_handles_plain_ascii():
    assert _decode_subject('plain subject') == 'plain subject'


def test_extract_plain_text_decodes_half_width_katakana_in_iso_2022_jp():
    """標準のiso2022_jpコーデックは半角カナのエスケープ(ESC(I)をサポートせず
    文字化けする(楽天カードの利用先が半角カナの店舗名の場合に実際に発生した)。
    iso2022_jp_extへのフォールバックで正しくデコードできること"""
    msg = MIMEText('', 'plain', 'iso-2022-jp')
    # 半角カナ「ｸﾗｽ」を ESC(I エスケープシーケンスでエンコードしたペイロードに差し替える
    raw_body = '利用先: ｸﾗｽ'.encode('iso2022_jp_ext')
    del msg['Content-Transfer-Encoding']
    msg.set_payload(raw_body.decode('latin-1'))
    msg['Content-Transfer-Encoding'] = '8bit'

    text = _extract_plain_text(msg)

    assert 'ｸﾗｽ' in text


def test_extract_plain_text_prefers_plain_over_html():
    msg = MIMEMultipart('alternative')
    msg.attach(MIMEText('プレーンテキスト本文', 'plain', 'utf-8'))
    msg.attach(MIMEText('<p>HTML本文</p>', 'html', 'utf-8'))

    text = _extract_plain_text(msg)

    assert text == 'プレーンテキスト本文'


def test_extract_plain_text_falls_back_to_html_when_no_plain_part():
    msg = MIMEMultipart('alternative')
    msg.attach(MIMEText('<p>ご利用日時：2026/07/08 17:31</p>', 'html', 'utf-8'))

    text = _extract_plain_text(msg)

    assert 'ご利用日時：2026/07/08 17:31' in text
    assert '<p>' not in text


def test_strip_html_tags_removes_tags_and_style_blocks():
    html = '<html><head><style>body{color:red}</style></head><body><p>本文</p></body></html>'
    text = _strip_html_tags(html)

    assert 'color:red' not in text
    assert '本文' in text
    assert '<p>' not in text
