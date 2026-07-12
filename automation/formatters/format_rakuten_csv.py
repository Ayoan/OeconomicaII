#!/usr/bin/env python3
"""
楽天カードWeb明細CSV整形スクリプト

楽天カードのWeb明細(csvファイル)を自作の家計簿アプリで
インポート可能な形式のcsvファイルに整形します。
"""

import csv
import glob
import os
import shutil
from datetime import datetime

from csv_formatter_common import (
    zenkaku_to_hankaku,
    convert_date_format_slash_to_hyphen,
    parse_amount,
    write_formatted_csv,
    get_script_directory,
    get_today_date_string,
    OUTPUT_FIELDNAMES
)


def format_rakuten_csv():
    """楽天カードのWeb明細CSVを家計簿アプリ用形式に整形

    処理済みの生CSVは formatters/datas/processed/ へ退避する。ブラウザの
    重複ダウンロード時のファイル名連番付与(例: enavi(1).csv)により
    formatters/datas/ 直下に古いファイルが溜まり続け、次回実行時に
    'enavi*.csv' の glob が最新でないファイルを拾ってしまう事故を防ぐため
    (2026-07-12、本番環境での実機検証で確認)。
    """

    # スクリプトのディレクトリを取得
    script_dir = get_script_directory()

    # 入力ファイルの検索（複数ある場合は最終更新が最新のものを使う）
    input_pattern = os.path.join(script_dir, 'datas', 'enavi*.csv')
    input_files = glob.glob(input_pattern)

    if not input_files:
        print(f"エラー: {input_pattern} にマッチするファイルが見つかりません")
        print(f"datasディレクトリに 'enavi' で始まるCSVファイルを配置してください")
        return

    if len(input_files) > 1:
        print(f"警告: 複数のファイルが見つかりました。最終更新が最新のファイルを処理します")

    input_file = max(input_files, key=os.path.getmtime)
    print(f"処理対象ファイル: {os.path.basename(input_file)}")

    # 出力ファイル名の生成
    today = get_today_date_string()
    output_file = os.path.join(script_dir, 'datas', f'enavi_{today}_formatted.csv')

    # CSVファイルの読み込みと処理
    formatted_data = []
    skipped_count = 0

    try:
        with open(input_file, 'r', encoding='utf-8-sig') as f:
            reader = csv.DictReader(f)

            for row in reader:
                # 外貨決済の明細行(利用日・利用金額が空欄で、利用店名・商品名に
                # 為替レート補足が入る行)はスキップする
                if not row['利用日'].strip():
                    skipped_count += 1
                    continue

                is_valid, amount = parse_amount(row['利用金額'])
                if not is_valid:
                    skipped_count += 1
                    continue

                # データ整形
                date = convert_date_format_slash_to_hyphen(row['利用日'])
                memo = zenkaku_to_hankaku(row['利用店名・商品名'])
                category = ''  # 空欄(作業者が入力)
                income_expense = '支出'

                formatted_data.append({
                    '日付': date,
                    '収支区分': income_expense,
                    'カテゴリ': category,
                    '金額': amount,
                    'メモ': memo
                })
    except Exception as e:
        print(f"エラー: ファイルの読み込み中に問題が発生しました - {e}")
        return

    # 整形後のCSVファイルを書き込み
    write_formatted_csv(output_file, formatted_data)

    # 処理済みの生CSV(未処理分・重複ダウンロード分も含め全て)をprocessed/へ退避する
    processed_dir = os.path.join(script_dir, 'datas', 'processed')
    os.makedirs(processed_dir, exist_ok=True)
    timestamp = datetime.now().strftime('%H%M%S')
    for i, raw_file in enumerate(input_files):
        name, ext = os.path.splitext(os.path.basename(raw_file))
        shutil.move(raw_file, os.path.join(processed_dir, f'{name}_{timestamp}_{i}{ext}'))

    print(f"\n整形完了!")
    print(f"出力ファイル: {os.path.basename(output_file)}")
    print(f"処理件数: {len(formatted_data)}件")
    if skipped_count > 0:
        print(f"スキップ件数: {skipped_count}件 (外貨決済の補足行等)")


if __name__ == '__main__':
    format_rakuten_csv()
