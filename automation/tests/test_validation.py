import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from validation import validate_records  # noqa: E402

CATEGORIES = {'income': ['給与'], 'expense': ['食費', '日用品', '不明']}


def make_record(date='2026-07-10', balance='支出', category='食費', amount=500, memo='セブンイレブン'):
    return {'日付': date, '収支区分': balance, 'カテゴリ': category, '金額': amount, 'メモ': memo}


def test_valid_record_passes():
    valid, errors = validate_records([make_record()], CATEGORIES)
    assert len(valid) == 1
    assert errors == []


def test_invalid_date_is_rejected():
    valid, errors = validate_records([make_record(date='2026/07/10')], CATEGORIES)
    assert valid == []
    assert len(errors) == 1


def test_invalid_balance_is_rejected():
    valid, errors = validate_records([make_record(balance='不明区分')], CATEGORIES)
    assert valid == []
    assert len(errors) == 1


def test_category_not_in_list_is_rejected():
    valid, errors = validate_records([make_record(category='存在しないカテゴリ')], CATEGORIES)
    assert valid == []
    assert len(errors) == 1


def test_amount_below_one_is_rejected():
    valid, errors = validate_records([make_record(amount=0)], CATEGORIES)
    assert valid == []
    assert len(errors) == 1


def test_non_numeric_amount_is_rejected():
    valid, errors = validate_records([make_record(amount='abc')], CATEGORIES)
    assert valid == []
    assert len(errors) == 1


def test_default_category_unmei_passes_when_registered():
    valid, errors = validate_records([make_record(category='不明')], CATEGORIES)
    assert len(valid) == 1
    assert errors == []


def test_mixed_valid_and_invalid_records():
    records = [make_record(), make_record(amount=0)]
    valid, errors = validate_records(records, CATEGORIES)
    assert len(valid) == 1
    assert len(errors) == 1
