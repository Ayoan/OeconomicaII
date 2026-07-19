"""
Gmail IMAP経由でカード利用通知メールを取得する汎用モジュール

VPASSの「ご利用通知サービス」（SMBCカード、送信元: statement@vpass.ne.jp）や
楽天カードの「カード利用のお知らせ」（送信元: info@mail.rakuten-card.co.jp）等、
複数のカード会社が送信する利用通知メールをIMAP経由で検索・取得する共通基盤。
両カードともSeleniumスクレイピングが安定稼働しない（SMBC: Akamai Bot Manager
によるブロック、e-navi: SPA初期化タイミング依存の断続的な失敗）と判断したため、
本モジュール経由のメール通知方式に切り替えた
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


def _encode_mailbox_name(name):
    """Gmailラベル名をIMAPの変更UTF-7（RFC 2060）へエンコードする

    imaplibはmailbox名をASCIIとしてそのまま送信するため、日本語ラベル
    （例: 'クレジットカード/楽天カード'）を含む階層ラベルは事前に
    エンコードしないと `UnicodeEncodeError` になる。'/' は日本語Gmail
    ラベルの階層区切りとして使われるため、セグメントごとに分けてから
    変換する（'/'そのものをbase64エンコード対象に含めてしまうと壊れる）。
    """
    return '/'.join(
        part.encode('utf-7').decode('ascii').replace('+', '&').replace('/', ',')
        for part in name.split('/')
    )


def fetch_matching_emails(email_config):
    """条件に合致するメールを取得し、本文(プレーンテキスト)のリストを返す

    Args:
        email_config (dict): config.json の EMAIL_SOURCES.<KEY> 相当の辞書
            (IMAP_HOST, IMAP_PORT, EMAIL_ADDRESS, APP_PASSWORD,
             SENDER_FILTER, SUBJECT_FILTER, LOOKBACK_DAYS, MAILBOX(任意),
             SUBJECT_EXACT_MATCH(任意))
            MAILBOX省略時は 'INBOX'。Gmailのフィルタで特定ラベルに自動振分けされている
            場合（例: 'クレジットカード/VPASS'）は、そのラベル名を指定する。
            階層ラベル（親ラベル/子ラベル）は '/' 区切りで指定する。日本語を含む
            ラベル名も指定可能（内部でIMAP変更UTF-7へ自動エンコードする）。
            SUBJECT_EXACT_MATCH省略時はFalse（部分一致）。楽天カードの
            「カード利用のお知らせ(本人ご利用分)」のように、類似件名の別メール
            （「【速報版】カード利用のお知らせ(本人ご利用分)」等、詳細情報を含まない
            速報メール）が部分一致で誤って混入するケースでは、Trueを指定して
            完全一致に限定する。

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
        imap.select(_encode_mailbox_name(email_config.get('MAILBOX', 'INBOX')))
        search_criteria = f'(FROM "{email_config["SENDER_FILTER"]}" SINCE {since_date})'
        status, data = imap.search(None, search_criteria)
        if status != 'OK':
            raise EmailFetchError(f"IMAP検索に失敗しました: {status}")

        subject_filter = email_config.get('SUBJECT_FILTER', '')
        subject_exact_match = email_config.get('SUBJECT_EXACT_MATCH', False)
        for msg_id in data[0].split():
            status, msg_data = imap.fetch(msg_id, '(RFC822)')
            if status != 'OK':
                continue
            msg = email.message_from_bytes(msg_data[0][1])

            subject = _decode_subject(msg.get('Subject', ''))
            if subject_filter:
                if subject_exact_match:
                    if subject.strip() != subject_filter:
                        continue
                elif subject_filter not in subject:
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
    if charset.lower().replace('_', '-') in ('iso-2022-jp',):
        # 標準のiso2022_jpコーデックは半角カナのエスケープシーケンス(ESC(I)を
        # サポートせずデコードエラー/文字化けになる(例: 楽天カードの利用先が
        # 半角カナの店舗名の場合)。iso2022_jp_extは上位互換で半角カナも扱えるため
        # iso-2022-jp宣言のメールは常にこちらでデコードする
        charset = 'iso2022_jp_ext'
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
