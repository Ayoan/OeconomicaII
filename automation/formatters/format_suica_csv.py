#!/usr/bin/env python3
"""
SuicaWeb明細CSV整形スクリプト

SuicaのWeb明細(tsvファイル)を自作の家計簿アプリで
インポート可能な形式のcsvファイルに整形します。
"""

import csv
import glob
import os
from datetime import datetime

from csv_formatter_common import (
    convert_date_format_with_year,
    parse_amount,
    write_formatted_csv,
    get_script_directory,
    get_today_date_string,
    OUTPUT_FIELDNAMES
)


def format_suica_csv():
    """SuicaのWeb明細TSVを家計簿アプリ用形式に整形"""

    # スクリプトのディレクトリを取得
    script_dir = get_script_directory()

    # 入力ファイルの検索
    input_pattern = os.path.join(script_dir, 'datas', '*suica*.tsv')
    input_files = glob.glob(input_pattern)

    if not input_files:
        print(f"エラー: {input_pattern} にマッチするファイルが見つかりません")
        print(f"datasディレクトリに 'suica' を含むTSVファイルを配置してください")
        return

    if len(input_files) > 1:
        print(f"警告: 複数のファイルが見つかりました。最初のファイルを処理します")

    input_file = input_files[0]
    print(f"処理対象ファイル: {os.path.basename(input_file)}")

    # 出力ファイル名の生成
    today = get_today_date_string()
    current_year = datetime.now().year
    output_file = os.path.join(script_dir, 'datas', f'suica_{today}_formatted.csv')

    # TSVファイルの読み込みと処理
    formatted_data = []
    skipped_count = 0

    try:
        with open(input_file, 'r', encoding='utf-8-sig') as f:
            # TSVファイルを読み込み（タブ区切り）
            reader = csv.reader(f, delimiter='\t')

            # ヘッダー行をスキップ
            header = next(reader, None)

            for row in reader:
                # 空行をスキップ
                if not row or all(cell.strip() == '' for cell in row):
                    continue

                # データが不足している行をスキップ
                if len(row) < 7:
                    continue

                # 各カラムを取得
                date_str = row[0].strip()  # 月日
                type1 = row[1].strip()  # 種別(1つ目)
                location1 = row[2].strip() if len(row) > 2 else ''  # 利用場所(1つ目)
                amount_str = row[6].strip() if len(row) > 6 else ''  # 入金・利用額

                # 日付が空の場合はスキップ
                if not date_str:
                    continue

                # 金額を解析
                is_valid, amount = parse_amount(amount_str)
                if not is_valid:
                    skipped_count += 1
                    continue

                # 日付を変換
                date = convert_date_format_with_year(date_str, current_year)

                # カテゴリとメモを決定
                if type1 == '物販':
                    category = '食費'
                    memo = ''
                elif type1 == '入':
                    category = '交通費'
                    # 利用場所をメモに設定
                    memo = f'利用場所:{location1}' if location1 else ''
                else:
                    # それ以外の種別はスキップ
                    skipped_count += 1
                    continue

                income_expense = '支出'

                formatted_data.append({
                    '日付': date,
                    '収支区分': income_expense,
                    'カテゴリ': category,
                    '金額': amount,
                    'メモ': memo
                })

    except UnicodeDecodeError:
        # UTF-8で読めない場合はShift_JISを試す
        print("UTF-8での読み込みに失敗しました。Shift_JISで再試行します。")
        with open(input_file, 'r', encoding='shift_jis') as f:
            reader = csv.reader(f, delimiter='\t')
            header = next(reader, None)

            for row in reader:
                if not row or all(cell.strip() == '' for cell in row):
                    continue

                if len(row) < 7:
                    continue

                date_str = row[0].strip()
                type1 = row[1].strip()
                location1 = row[2].strip() if len(row) > 2 else ''
                amount_str = row[6].strip() if len(row) > 6 else ''

                if not date_str:
                    continue

                is_valid, amount = parse_amount(amount_str)
                if not is_valid:
                    skipped_count += 1
                    continue

                date = convert_date_format_with_year(date_str, current_year)

                if type1 == '物販':
                    category = '食費'
                    memo = ''
                elif type1 == '入':
                    category = '交通費'
                    memo = f'利用場所:{location1}' if location1 else ''
                else:
                    skipped_count += 1
                    continue

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
    if skipped_count > 0:
        print(f"スキップ件数: {skipped_count}件 (入金・対象外種別)")


if __name__ == '__main__':
    format_suica_csv()
