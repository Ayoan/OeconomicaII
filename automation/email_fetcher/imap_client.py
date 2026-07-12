"""
Gmail IMAP経由でSMBCカードの利用通知メールを取得するモジュール

VPASSの「ご利用通知サービス」で送信される支払い通知メール
(送信元: statement@vpass.ne.jp)を検索・取得する。Seleniumスクレイピングは
SMBC側のAkamai Bot Managerによるブロックで安定稼働しないと判断したため、
SMBCカードの明細取得は本モジュール経由のメール通知方式に切り替えた
(経緯: Docs/設計書_家計簿登録AI効率化_システム設計.md 9.5節参照)。

同じメールを重複取得しても、DB側の重複排除(dedup.py)がfingerprintベースで
弾くため、本モジュールでは既読管理・UID管理等の状態は持たず、毎回
LOOKBACK_DAYS日分の該当メールを取得するだけのシンプルな実装とする。
"""

import email
import imaplib
import re
from datetime import datetime, timedelta
from email.header import decode_header


class EmailFetchError(Exception):
    """IMAP接続・検索に失敗した場合の例外"""


def fetch_matching_emails(email_config):
    """条件に合致するメールを取得し、本文(プレーンテキスト)のリストを返す

    Args:
        email_config (dict): config.json の EMAIL_SOURCES.<KEY> 相当の辞書
            (IMAP_HOST, IMAP_PORT, EMAIL_ADDRESS, APP_PASSWORD,
             SENDER_FILTER, SUBJECT_FILTER, LOOKBACK_DAYS, MAILBOX(任意))
            MAILBOX省略時は 'INBOX'。Gmailのフィルタで特定ラベルに自動振分けされている
            場合（例: 'VPASS'）は、そのラベル名を指定する。

    Returns:
        list[str]: 条件に合致したメールの本文(プレーンテキスト)のリスト

    Raises:
        EmailFetchError: IMAP接続・認証・検索に失敗した場合
    """
    lookback_days = email_config.get('LOOKBACK_DAYS', 7)
    since_date = (datetime.now() - timedelta(days=lookback_days)).strftime('%d-%b-%Y')

    try:
        imap = imaplib.IMAP4_SSL(email_config['IMAP_HOST'], email_config.get('IMAP_PORT', 993))
        imap.login(email_config['EMAIL_ADDRESS'], email_config['APP_PASSWORD'])
    except Exception as exc:
        raise EmailFetchError(f"IMAP接続/認証に失敗しました: {exc}") from exc

    bodies = []
    try:
        imap.select(email_config.get('MAILBOX', 'INBOX'))
        search_criteria = f'(FROM "{email_config["SENDER_FILTER"]}" SINCE {since_date})'
        status, data = imap.search(None, search_criteria)
        if status != 'OK':
            raise EmailFetchError(f"IMAP検索に失敗しました: {status}")

        subject_filter = email_config.get('SUBJECT_FILTER', '')
        for msg_id in data[0].split():
            status, msg_data = imap.fetch(msg_id, '(RFC822)')
            if status != 'OK':
                continue
            msg = email.message_from_bytes(msg_data[0][1])

            subject = _decode_subject(msg.get('Subject', ''))
            if subject_filter and subject_filter not in subject:
                continue

            body = _extract_plain_text(msg)
            if body:
                bodies.append(body)
    finally:
        imap.logout()

    return bodies


def _decode_subject(raw_subject):
    """MIMEエンコードされた件名をデコードする"""
    parts = []
    for text, charset in decode_header(raw_subject):
        if isinstance(text, bytes):
            parts.append(text.decode(charset or 'utf-8', errors='replace'))
        else:
            parts.append(text)
    return ''.join(parts)


def _extract_plain_text(msg):
    """メール本文からプレーンテキストを抽出する

    text/plainパートがあればそれを使い、無ければtext/htmlパートから
    タグを除去したテキストを使う。
    """
    if msg.is_multipart():
        plain_part = None
        html_part = None
        for part in msg.walk():
            content_type = part.get_content_type()
            if content_type == 'text/plain' and plain_part is None:
                plain_part = part
            elif content_type == 'text/html' and html_part is None:
                html_part = part
        target = plain_part or html_part
    else:
        target = msg

    if target is None:
        return ''

    payload = target.get_payload(decode=True)
    if payload is None:
        return ''

    charset = target.get_content_charset() or 'utf-8'
    text = payload.decode(charset, errors='replace')

    if target.get_content_type() == 'text/html':
        text = _strip_html_tags(text)

    return text


def _strip_html_tags(html):
    """HTML文字列からタグを除去してプレーンテキスト相当にする"""
    text = re.sub(r'<(script|style)[^>]*>.*?</\1>', '', html, flags=re.DOTALL | re.IGNORECASE)
    text = re.sub(r'<[^>]+>', '\n', text)
    text = text.replace('&nbsp;', ' ')
    text = re.sub(r'[ \t]+\n', '\n', text)
    text = re.sub(r'\n{2,}', '\n', text)
    return text.strip()
