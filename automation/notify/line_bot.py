"""
LINE Bot 通知モジュール

実行結果サマリ（登録件数・不明カテゴリ数等）、およびスクレイピング失敗時の
例外通知（HTML構造不一致の可能性）をLINE Messaging APIで送信する。
"""

import requests

LINE_PUSH_URL = 'https://api.line.me/v2/bot/message/push'


def send_line_message(message, config, session=requests):
    """LINE Messaging API でユーザーへプッシュメッセージを送信する

    Args:
        message (str): 送信するテキスト
        config (dict): config.json の内容（LINE_BOT_CONFIG.CHANNEL_ACCESS_TOKEN / USER_ID を使用）
        session: requests互換オブジェクト（テスト時にモックを注入するためのフック）

    Returns:
        requests.Response
    """
    line_config = config['LINE_BOT_CONFIG']
    headers = {
        'Content-Type': 'application/json',
        'Authorization': f"Bearer {line_config['CHANNEL_ACCESS_TOKEN']}",
    }
    payload = {
        'to': line_config['USER_ID'],
        'messages': [{'type': 'text', 'text': message}],
    }
    response = session.post(LINE_PUSH_URL, headers=headers, json=payload, timeout=10)
    response.raise_for_status()
    return response


def build_summary_message(inserted, errors, skipped_sources=None):
    """パイプライン実行結果からLINE通知用のサマリメッセージを組み立てる

    Args:
        inserted (int): 新規登録件数
        errors (list[str]): バリデーションエラーメッセージ群
        skipped_sources (list[str] | None): 取得に失敗し取り込みをスキップしたデータソース名

    Returns:
        str
    """
    lines = ['【家計簿自動登録】実行結果', f'新規登録: {inserted}件']
    if errors:
        lines.append(f'不明カテゴリ等のエラー: {len(errors)}件')
    if skipped_sources:
        lines.append(f'取得失敗のためスキップ: {", ".join(skipped_sources)}')
    if not errors and not skipped_sources:
        lines.append('エラーなし')
    return '\n'.join(lines)


def build_structure_error_message(source_name, detail):
    """スクレイピング失敗時のエラー通知メッセージを組み立てる

    Args:
        source_name (str): 失敗したデータソース名（例: 'e-navi'）
        detail (str): 失敗理由（例外メッセージ）

    Returns:
        str
    """
    return (
        f'【家計簿自動登録】{source_name} の明細取得に失敗しました\n'
        f'原因: {detail}\n'
        'サイトのHTML構造が変わった可能性があります。'
        'config.json の対象XPATH値を確認・修正してください。'
    )
