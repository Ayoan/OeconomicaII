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

    通知BotのLINEアイコンがポムポムプリンのため、メッセージの文面もプリンが
    喋っている口調（一人称「ぼく」、柔らかい語尾、のんびりした雰囲気）で
    統一している（Docs/ポムポムプリン口調.md 参照）。

    Args:
        inserted (int): 新規登録件数
        errors (list[str]): バリデーションエラーメッセージ群
        skipped_sources (list[str] | None): 取得に失敗し取り込みをスキップしたデータソース名

    Returns:
        str
    """
    lines = ['🍮 プリンだよ、今日のお仕事の報告だよ〜']

    if inserted > 0:
        lines.append(f'カードのお支払いを{inserted}件見つけて、家計簿にちゃんと書いておいたよ！')
    elif not skipped_sources:
        # 取得自体は成功した上で新規0件（＝本当に新しい支払いが無かった）場合のみ
        # 「お昼寝」の表現を使う。取得失敗時に紛らわしくなるのを避けるため区別する。
        lines.append('今日は新しいお支払いはなかったみたい。のんびりお昼寝できたよ〜。')

    if errors:
        lines.append(f'ただ、{len(errors)}件だけカテゴリがよくわからなくて「不明」のままにしちゃったんだ。あとで見てくれると嬉しいな。')

    if skipped_sources:
        sources = '、'.join(skipped_sources)
        lines.append(f'{sources}のお知らせがうまく受け取れなくて、お休みしちゃったよ。ごめんね、確認してもらえるかな？')

    if not errors and not skipped_sources:
        lines.append('エラーもなくて、今日ものんびり平和な一日だったよ♪')

    return '\n'.join(lines)


def build_structure_error_message(source_name, detail):
    """明細取得失敗時のエラー通知メッセージを組み立てる

    スクレイピング失敗（HTML構造変化）・メール取得失敗（IMAP接続エラー）の
    両方から呼び出される想定のため、原因箇所を限定しない書き方にしている。

    Args:
        source_name (str): 失敗したデータソース名（例: 'e-navi'）
        detail (str): 失敗理由（例外メッセージ）

    Returns:
        str
    """
    return (
        '🍮 プリンだよ、ちょっと困ったことがあったんだ…\n'
        f'{source_name}の明細がうまく取れなかったよ。\n'
        f'理由: {detail}\n'
        'サイトかメールの形が変わっちゃったのかもしれないから、確認してもらえると嬉しいな。'
    )
