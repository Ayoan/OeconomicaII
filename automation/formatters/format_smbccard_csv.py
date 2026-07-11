#!/usr/bin/env python3
"""
SMBC Card Web明細CSV整形スクリプト

SMBCカードのWeb明細(csvファイル)を自作の家計簿アプリで
インポート可能な形式のcsvファイルに整形します。
"""

import argparse
import csv
import glob
import os
from datetime import datetime

from csv_formatter_common import (
    zenkaku_to_hankaku,
    convert_date_format_slash_to_hyphen,
    write_formatted_csv,
    get_script_directory,
    get_today_date_string,
    OUTPUT_FIELDNAMES
)


def format_smbccard_csv(input_filename=None):
    """SMBCのWeb明細TSVを家計簿アプリ用形式に整形

    Args:
        input_filename: 入力ファイル名（省略時は現在の年月から自動生成）
    """

    # スクリプトのディレクトリを取得
    script_dir = get_script_directory()

    # 入力ファイルの決定
    if input_filename:
        # 引数で指定されたファイル名を使用
        input_file = os.path.join(script_dir, 'datas', input_filename)
    else:
        # 引数が指定されていない場合は、現在の年月から自動生成
        today_yyyymm = get_today_date_string('%Y%m')
        print(today_yyyymm)
        input_file = os.path.join(script_dir, 'datas', f'{today_yyyymm}.csv')

    print(f"処理対象ファイル: {os.path.basename(input_file)}")

    # 出力ファイル名の生成
    today = get_today_date_string()
    output_file = os.path.join(script_dir, 'datas', f'smbccard_{today}_formatted.csv')

    # CSVファイルの読み込みと処理
    formatted_data = []

    # 試行するエンコーディング順（utf-8-sig はBOM付きUTF-8も処理できる）
    encodings = ['utf-8-sig', 'utf-8', 'shift_jis', 'cp932']
    encoding_used = None
    for enc in encodings:
        try:
            with open(input_file, 'r', encoding=enc) as f:
                f.read()
            encoding_used = enc
            break
        except UnicodeDecodeError:
            continue

    if encoding_used is None:
        raise ValueError(f"対応するエンコーディングで読み込めませんでした: {input_file}")

    if encoding_used not in ('utf-8-sig', 'utf-8'):
        print(f"{encoding_used}で読み込みます。")

    with open(input_file, 'r', encoding=encoding_used) as f:
        for raw_line in f:
            raw_line = raw_line.rstrip('\n')

            if not raw_line.strip():
                continue

            # csvモジュールで1行分をパースしてリストにする
            row = next(csv.reader([raw_line]))

            # 必要なカラム数を満たしているか確認
            if len(row) < 7:
                continue

            # 日付が空の行（合計行など）はスキップ
            if not row[0].strip():
                continue

            # 日付形式でない行（ヘッダー行など）はスキップ
            if '/' not in row[0]:
                continue

            # 値を取得
            date = convert_date_format_slash_to_hyphen(row[0].strip()) # yyyy/mm/dd形式をyyyy-mm-dd形式に変換

            # 8カラム以上の場合は外貨決済の明細（店舗名が2カラムに分割されている）
            if len(row) >= 8:
                memo = zenkaku_to_hankaku(row[1].strip() + row[2].strip())
                amount = row[6].strip()
            else:
                # 通常の7カラムの明細
                memo = zenkaku_to_hankaku(row[1].strip())
                amount = row[5].strip()

            category = ''  # 空欄(作業者が入力)
            income_expense = '支出'

            formatted_data.append({
                '日付': date,
                '収支区分': income_expense,
                'カテゴリ': category,
                '金額': amount,
                'メモ': memo
            })

    # 整形後のCSVファイルを書き込み
    write_formatted_csv(output_file, formatted_data)

    print(f"\n整形完了!")
    print(f"出力ファイル: {os.path.basename(output_file)}")
    print(f"処理件数: {len(formatted_data)}件")

if __name__ == '__main__':
    parser = argparse.ArgumentParser(
        description='SMBCカードのWeb明細CSVを家計簿アプリ用形式に整形します。'
    )
    parser.add_argument(
        'filename',
        nargs='?',
        help='入力ファイル名（省略時は現在の年月から自動生成: YYYYMM.csv）'
    )

    args = parser.parse_args()
    format_smbccard_csv(args.filename)
