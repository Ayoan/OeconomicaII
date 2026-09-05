"""
除外フィルタモジュール

Formatter層が出力した共通スキーマレコードのうち、exclusion_mapping.json の
キーワードに部分一致するメモを持つレコードを家計簿登録対象から除外する。

NISA積立等の資産振替のように、カード明細上は引き落としとして現れるものの
家計簿上の「支出」としては扱いたくない項目を対象とする想定
（category_mapping.jsonのキーワード一致方式と対になる仕組み）。
"""

import json


def load_exclusion_mapping(mapping_path):
    """exclusion_mapping.json を読み込む

    Args:
        mapping_path (str): exclusion_mapping.json のパス

    Returns:
        dict: {'text_contains_exclusions': [{'keyword': str, 'reason': str}, ...]}
    """
    with open(mapping_path, 'r', encoding='utf-8') as f:
        return json.load(f)


def is_excluded(memo, mapping):
    """メモが除外対象キーワードのいずれかに部分一致するか判定する

    category_mapper.resolve_category() と同様、大文字小文字を区別しない。

    Args:
        memo (str): メモ文字列（店舗名等）
        mapping (dict): load_exclusion_mapping() の戻り値

    Returns:
        bool: 除外対象なら True
    """
    memo_lower = memo.lower()
    return any(
        rule['keyword'].lower() in memo_lower
        for rule in mapping.get('text_contains_exclusions', [])
    )


def filter_excluded_records(records, mapping):
    """レコード群から除外対象を取り除く

    Args:
        records (list[dict]): 共通スキーマのレコード群
        mapping (dict): load_exclusion_mapping() の戻り値

    Returns:
        list[dict]: 除外対象を取り除いたレコード群（新しいリスト、元データは変更しない）
    """
    return [r for r in records if not is_excluded(r.get('メモ', ''), mapping)]
