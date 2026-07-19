#!/usr/bin/env python3
"""
CSV整形ツール共通モジュール

各種CSV整形ツールで共通する処理を提供します。
"""

import csv
import os
import re
import unicodedata
from datetime import datetime


# 出力CSVのフィールド名（全ツール共通）
OUTPUT_FIELDNAMES = ['日付', '収支区分', 'カテゴリ', '金額', 'メモ']


def zenkaku_to_hankaku(text):
    """全角英数字・記号を半角に、半角カナを全角カナに変換し、
    連続する半角スペースを一つにまとめる（店舗名表記のフォント統一）

    半角カナはメールのISO-2022-JPエンコード等に由来し（楽天カード利用通知
    メールで実際に確認）、他の登録経路（CSVインポート等）の全角カナ表記と
    混在すると表示フォントが不揃いになるため、NFKC正規化で全角カナへ統一する。
    NFKCは全角英数字・記号の半角化も兼ねるが、全角マイナス記号(U+2212)は
    対象外のため後段の個別変換で拾う。

    Args:
        text (str): 変換対象の文字列

    Returns:
        str: 正規化された文字列（連続スペースは一つに変換）
    """
    text = unicodedata.normalize('NFKC', text)
    result = []
    for char in text:
        code = ord(char)
        # 全角英数字・記号の範囲 (0xFF01-0xFF5E)
        if 0xFF01 <= code <= 0xFF5E:
            result.append(chr(code - 0xFEE0))
        # 全角スペース
        elif code == 0x3000:
            result.append(' ')
        # 全角マイナス記号(U+2212)。0xFF01-0xFF5Eの範囲外だが店舗名等に頻出するため個別対応
        elif code == 0x2212:
            result.append('-')
        else:
            result.append(char)

    # 連続する半角スペースを一つにまとめる
    converted = ''.join(result)
    return re.sub(r' +', ' ', converted)


def convert_date_format_slash_to_hyphen(date_str):
    """日付形式を変換（yyyy/mm/dd → yyyy-mm-dd）

    Args:
        date_str (str): yyyy/mm/dd形式の日付文字列

    Returns:
        str: yyyy-mm-dd形式の日付文字列
    """
    return date_str.replace('/', '-')


def convert_date_format_with_year(date_str, current_year):
    """日付形式を変換（mm/dd → yyyy-mm-dd）

    Args:
        date_str (str): mm/dd形式の日付文字列
        current_year (int): 現在の西暦

    Returns:
        str: yyyy-mm-dd形式の日付文字列
    """
    # mm/ddをパース
    month, day = date_str.split('/')
    # yyyy-mm-dd形式に変換
    return f"{current_year}-{month.zfill(2)}-{day.zfill(2)}"


def parse_amount(amount_str):
    """金額を解析

    Args:
        amount_str (str): 金額文字列 (例: "-361", "+5,000")

    Returns:
        tuple: (is_valid, amount)
            is_valid: 処理対象かどうか（+で始まる場合はFalse）
            amount: 数値化された金額
    """
    # 空白を除去
    amount_str = amount_str.strip()

    # +で始まる場合は除外
    if amount_str.startswith('+'):
        return False, 0

    # -で始まる場合は除去
    if amount_str.startswith('-'):
        amount_str = amount_str[1:]

    # カンマを除去して数値化
    amount_str = amount_str.replace(',', '')

    try:
        amount = int(amount_str)
        return True, amount
    except ValueError:
        # 数値化できない場合はスキップ
        return False, 0


def write_formatted_csv(output_file, formatted_data):
    """整形後のCSVファイルを書き込み

    Args:
        output_file (str): 出力ファイルパス
        formatted_data (list): 整形済みデータのリスト

    Returns:
        None
    """
    with open(output_file, 'w', encoding='utf-8', newline='') as f:
        writer = csv.DictWriter(f, fieldnames=OUTPUT_FIELDNAMES)
        writer.writeheader()
        writer.writerows(formatted_data)


def get_script_directory():
    """スクリプトのディレクトリを取得

    Returns:
        str: スクリプトのディレクトリパス
    """
    return os.path.dirname(os.path.abspath(__file__))


def get_today_date_string(format_string='%Y%m%d'):
    """今日の日付を文字列で取得

    Args:
        format_string (str): 日付フォーマット文字列（デフォルト: '%Y%m%d'）

    Returns:
        str: フォーマットされた今日の日付文字列
    """
    return datetime.now().strftime(format_string)
