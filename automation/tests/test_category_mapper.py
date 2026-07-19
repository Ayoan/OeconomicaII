import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from category_mapper import apply_category_mapping, resolve_category  # noqa: E402

MAPPING = {
    'text_contains_mappings': [
        {'keyword': '京都生活協同組合', 'category': '食費'},
        {'keyword': 'セブン-イレブン', 'category': '日用品'},
    ],
    'default_category': '不明',
}


def test_resolve_category_matches_keyword():
    assert resolve_category('セブン-イレブン 渋谷店', MAPPING) == '日用品'


def test_resolve_category_falls_back_to_default():
    assert resolve_category('謎の店舗ABC', MAPPING) == '不明'


def test_resolve_category_is_case_insensitive():
    """'Notion'/'NOTION'/'NOTION LABS, INC.'等、決済代行会社側の大文字小文字
    表記ゆれを1ルールで吸収できること(2026-07-19、カテゴリマッピング整備時に
    実データで確認した表記ゆれパターン)"""
    mapping = {
        'text_contains_mappings': [{'keyword': 'Notion', 'category': 'サブスク'}],
        'default_category': '不明',
    }
    assert resolve_category('NOTION LABS, INC.', mapping) == 'サブスク'
    assert resolve_category('notion.so', mapping) == 'サブスク'


def test_resolve_category_uses_first_matching_rule_in_order():
    """キーワードが包含関係の場合(例:「ジャンプ＋」は「ジャンプ」を含む)、
    より具体的なキーワードを先に置くことで正しく区別できること"""
    mapping = {
        'text_contains_mappings': [
            {'keyword': 'ジャンプ＋', 'category': 'サブスク'},
            {'keyword': 'ジャンプ', 'category': '書籍'},
        ],
        'default_category': '不明',
    }
    assert resolve_category('ジャンプ＋', mapping) == 'サブスク'
    assert resolve_category('少年ジャンプ', mapping) == '書籍'


def test_apply_category_mapping_fills_empty_category_only():
    records = [
        {'日付': '2026-07-10', '収支区分': '支出', 'カテゴリ': '', '金額': 300, 'メモ': 'セブン-イレブン 渋谷店'},
        {'日付': '2026-07-10', '収支区分': '支出', 'カテゴリ': '食費', '金額': 200, 'メモ': ''},  # Suica由来で既に設定済み
    ]
    result = apply_category_mapping(records, MAPPING)

    assert result[0]['カテゴリ'] == '日用品'
    assert result[1]['カテゴリ'] == '食費'  # 上書きされない


def test_apply_category_mapping_does_not_mutate_input():
    records = [{'日付': '2026-07-10', '収支区分': '支出', 'カテゴリ': '', '金額': 300, 'メモ': '謎の店舗'}]
    apply_category_mapping(records, MAPPING)
    assert records[0]['カテゴリ'] == ''
