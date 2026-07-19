#!/usr/bin/env python3
"""
楽天カード「カード利用のお知らせ」メール整形スクリプト

e-naviのSeleniumスクレイピングは、2026-07にSPA初期化タイミング依存の
ログイン断続失敗（POST_PAGE_LOAD_DELAY_SECONDS・page_load_strategy=eager等
複数の対策を講じてもなお解消しない）が続き、cron無人運用に必要な安定性を
満たさなかったため、SMBCカード同様、カード利用の都度届く
「カード利用のお知らせ(本人ご利用分)」メール（送信元:
info@mail.rakuten-card.co.jp）をIMAP経由で取得しパースする方式に切り替えた
(経緯: Docs/設計書_家計簿登録AI効率化_システム設計.md 9.5節参照)。

「【速報版】カード利用のお知らせ(本人ご利用分)」という部分一致してしまう
類似件名の別メールが存在し、こちらは利用先・支払月等の詳細情報を含まない
速報のため、SUBJECT_FILTERは完全一致で絞り込むこと
(config.jsonのSUBJECT_EXACT_MATCH: true、imap_client.py参照)。

SMBCの通知メールが1通1件形式なのに対し、楽天カードの通知メールは1通に
複数件の利用明細が列挙される（繰り返しブロック）ため、本文中の
■利用日〜■支払月のブロックを全件抽出する。

メール本文は以下の固定フォーマット（繰り返し）:
    ■利用日: 2026/07/10
    ■利用先: ｸﾗｽ
    ■利用者: 本人
    ■支払方法: 1回
    ■利用金額: 997 円
    ■支払月: 2026/08
"""

import os
import re
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from csv_formatter_common import (  # noqa: E402
    get_script_directory,
    get_today_date_string,
    parse_amount,
    write_formatted_csv,
    zenkaku_to_hankaku,
)
from email_fetcher.imap_client import EmailFetchError, fetch_matching_emails  # noqa: E402

TRANSACTION_PATTERN = re.compile(
    r'■利用日\s*[：:]\s*(\d{4})/(\d{1,2})/(\d{1,2})\s*\r?\n'
    r'■利用先\s*[：:]\s*(.+?)\s*\r?\n'
    r'■利用者\s*[：:]\s*(.+?)\s*\r?\n'
    r'■支払方法\s*[：:]\s*(.+?)\s*\r?\n'
    r'■利用金額\s*[：:]\s*([\d,]+)\s*円'
)


def parse_email_body(body):
    """メール本文から利用明細(1通に複数件)を抽出する

    Args:
        body (str): メール本文(プレーンテキスト)

    Returns:
        list[dict]: [{'日付': 'YYYY-MM-DD', '収支区分': '支出', 'カテゴリ': '',
            '金額': int, 'メモ': str}, ...] 形式。金額が処理対象外の明細は除外する。
            該当する明細が1件も無ければ空リスト
    """
    records = []
    for match in TRANSACTION_PATTERN.finditer(body):
        year, month, day, place, _payer, _pay_method, amount_str = match.groups()
        date = f"{year}-{month.zfill(2)}-{day.zfill(2)}"
        memo = zenkaku_to_hankaku(place.strip())

        is_valid, amount = parse_amount(amount_str)
        if not is_valid:
            continue

        records.append({'日付': date, '収支区分': '支出', 'カテゴリ': '', '金額': amount, 'メモ': memo})

    return records


def format_rakuten_email(email_config):
    """楽天カードのカード利用お知らせメールを取得し、家計簿アプリ用形式に整形する

    Args:
        email_config (dict): config.json の EMAIL_SOURCES.RAKUTEN_NOTIFICATION 相当の辞書

    Returns:
        str | None: 出力ファイルパス。対象メールが1件も無ければ None

    Raises:
        EmailFetchError: IMAP接続・認証・検索に失敗した場合（呼び出し側で
            スクレイピング失敗時と同様にLINE通知することを想定し、ここでは
            もみ消さずに送出する）
    """
    bodies = fetch_matching_emails(email_config)

    formatted_data = []
    skipped_count = 0
    for body in bodies:
        records = parse_email_body(body)
        if not records:
            skipped_count += 1
            continue
        formatted_data.extend(records)

    if not formatted_data:
        print("対象メールが見つかりませんでした")
        return None

    script_dir = get_script_directory()
    today = get_today_date_string()
    output_file = os.path.join(script_dir, 'datas', f'rakuten_email_{today}_formatted.csv')
    write_formatted_csv(output_file, formatted_data)

    print(f"\n整形完了!")
    print(f"出力ファイル: {os.path.basename(output_file)}")
    print(f"処理件数: {len(formatted_data)}件")
    if skipped_count > 0:
        print(f"スキップ件数: {skipped_count}件 (本文解析失敗)")

    return output_file


if __name__ == '__main__':
    import json

    config_path = os.path.join(os.path.dirname(get_script_directory()), 'config.json')
    with open(config_path, 'r', encoding='utf-8') as f:
        config = json.load(f)

    format_rakuten_email(config['EMAIL_SOURCES']['RAKUTEN_NOTIFICATION'])
