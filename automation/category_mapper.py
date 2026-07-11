"""
カテゴリ自動補完モジュール

Formatter層が出力した共通スキーマレコードのうち、カテゴリが未設定
（例: format_smbccard_csv.py はカテゴリを空欄で出力し、作業者/AIが後で埋める想定）
のものに対して、category_mapping.json のキーワード一致ルールでカテゴリを自動付与する。
一致しない場合は default_category（「不明」）を付与する。
"""

import json


def load_category_mapping(mapping_path):
    """category_mapping.json を読み込む

    Args:
        mapping_path (str): category_mapping.json のパス

    Returns:
        dict: {'text_contains_mappings': [...], 'default_category': str}
    """
    with open(mapping_path, 'r', encoding='utf-8') as f:
        return json.load(f)


def resolve_category(memo, mapping):
    """メモの内容からキーワード一致でカテゴリを決定する

    Args:
        memo (str): メモ文字列（店舗名等）
        mapping (dict): load_category_mapping() の戻り値

    Returns:
        str: 一致したカテゴリ。一致しなければ default_category
    """
    for rule in mapping.get('text_contains_mappings', []):
        if rule['keyword'] in memo:
            return rule['category']
    return mapping.get('default_category', '不明')


def apply_category_mapping(records, mapping):
    """レコード群のうち、カテゴリ未設定のものにカテゴリを自動付与する

    既にFormatter層でカテゴリが設定されているレコード（例: Suicaの食費/交通費）は
    上書きしない。

    Args:
        records (list[dict]): 共通スキーマのレコード群
        mapping (dict): load_category_mapping() の戻り値

    Returns:
        list[dict]: カテゴリ補完後のレコード群（新しいリスト、元データは変更しない）
    """
    result = []
    for record in records:
        new_record = dict(record)
        if not new_record.get('カテゴリ'):
            new_record['カテゴリ'] = resolve_category(new_record.get('メモ', ''), mapping)
        result.append(new_record)
    return result
