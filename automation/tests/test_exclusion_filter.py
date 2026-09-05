import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from exclusion_filter import filter_excluded_records, is_excluded  # noqa: E402

MAPPING = {
    'text_contains_exclusions': [
        {'keyword': '楽天証券投信積立0.5%', 'reason': 'NISA積立への振替であり支出ではないため'},
    ],
}


def test_is_excluded_matches_keyword():
    assert is_excluded('楽天証券投信積立0.5%~', MAPPING) is True


def test_is_excluded_is_case_insensitive():
    mapping = {'text_contains_exclusions': [{'keyword': 'nisa', 'reason': 'test'}]}
    assert is_excluded('NISA積立', mapping) is True


def test_is_excluded_returns_false_for_non_matching_memo():
    assert is_excluded('セブン-イレブン 渋谷店', MAPPING) is False


def test_is_excluded_returns_false_when_no_rules():
    assert is_excluded('何でもいい', {'text_contains_exclusions': []}) is False


def test_filter_excluded_records_removes_matching_memo_only():
    records = [
        {'日付': '2026-07-10', '収支区分': '支出', 'カテゴリ': '', '金額': 50000, 'メモ': '楽天証券投信積立0.5%~'},
        {'日付': '2026-07-10', '収支区分': '支出', 'カテゴリ': '', '金額': 300, 'メモ': 'セブン-イレブン 渋谷店'},
    ]
    result = filter_excluded_records(records, MAPPING)

    assert len(result) == 1
    assert result[0]['メモ'] == 'セブン-イレブン 渋谷店'


def test_filter_excluded_records_does_not_mutate_input():
    records = [{'日付': '2026-07-10', '収支区分': '支出', 'カテゴリ': '', '金額': 50000, 'メモ': '楽天証券投信積立0.5%~'}]
    filter_excluded_records(records, MAPPING)
    assert len(records) == 1
