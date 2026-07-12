#!/usr/bin/env python3
"""
SMBCカード「ご利用通知サービス」メール整形スクリプト

VpassのAkamai Bot Managerによるスクレイピングブロックが解消しなかったため、
SMBCカードの明細取得はスクレイピングではなく、カード利用の都度届く
「ご利用のお知らせ【三井住友カード】」メール（本人カード分・家族カード分の両方、
送信元: statement@vpass.ne.jp）をIMAP経由で取得しパースする方式に切り替えた
(経緯: Docs/設計書_家計簿登録AI効率化_システム設計.md 9.5節参照)。

メール本文は以下の固定フォーマット:
    ◇利用日：2026/07/05 10:25
    ◇利用先：BURIAN KITAYAMATEN
    ◇利用取引：買物
    ◇利用金額：3,300円
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

USAGE_DATE_PATTERN = re.compile(r'◇利用日\s*[：:]\s*(\d{4})/(\d{1,2})/(\d{1,2})')
USAGE_PLACE_PATTERN = re.compile(r'◇利用先\s*[：:]\s*(.+)')
USAGE_AMOUNT_PATTERN = re.compile(r'◇利用金額\s*[：:]\s*([\d,]+)\s*円')


def parse_email_body(body):
    """メール本文から利用日・利用先・利用金額を抽出する

    Args:
        body (str): メール本文(プレーンテキスト)

    Returns:
        dict | None: {'日付': 'YYYY-MM-DD', 'メモ': str, '金額': int} 形式。
            必須項目が抽出できない、または金額が処理対象外(入金等)の場合は None
    """
    date_match = USAGE_DATE_PATTERN.search(body)
    place_match = USAGE_PLACE_PATTERN.search(body)
    amount_match = USAGE_AMOUNT_PATTERN.search(body)

    if not (date_match and place_match and amount_match):
        return None

    year, month, day = date_match.groups()
    date = f"{year}-{month.zfill(2)}-{day.zfill(2)}"

    memo = zenkaku_to_hankaku(place_match.group(1).strip())

    is_valid, amount = parse_amount(amount_match.group(1))
    if not is_valid:
        return None

    return {'日付': date, '収支区分': '支出', 'カテゴリ': '', '金額': amount, 'メモ': memo}


def format_smbc_email(email_config):
    """SMBCカードのご利用通知メールを取得し、家計簿アプリ用形式に整形する

    Args:
        email_config (dict): config.json の EMAIL_SOURCES.SMBC_NOTIFICATION 相当の辞書

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
        record = parse_email_body(body)
        if record is None:
            skipped_count += 1
            continue
        formatted_data.append(record)

    if not formatted_data:
        print("対象メールが見つかりませんでした")
        return None

    script_dir = get_script_directory()
    today = get_today_date_string()
    output_file = os.path.join(script_dir, 'datas', f'smbc_email_{today}_formatted.csv')
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

    format_smbc_email(config['EMAIL_SOURCES']['SMBC_NOTIFICATION'])
