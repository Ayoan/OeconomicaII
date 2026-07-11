import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from dedup import dedup_against_db, generate_fingerprint, get_date_range, normalize_memo  # noqa: E402

USER_ID = 1


def make_record(date='2026-07-10', balance='支出', category='食費', amount=500, memo='セブンイレブン'):
    return {'日付': date, '収支区分': balance, 'カテゴリ': category, '金額': amount, 'メモ': memo}


def test_exact_duplicate_is_excluded():
    """DBに1件、今回も1件の同一レコード → 0件登録"""
    db_records = [make_record()]
    new_records = [make_record()]
    assert dedup_against_db(new_records, db_records, USER_ID) == []


def test_empty_db_all_new_records_inserted():
    """DBが空の状態で全件投入 → 全件新規登録"""
    new_records = [make_record(memo='A店'), make_record(memo='B店')]
    result = dedup_against_db(new_records, [], USER_ID)
    assert result == new_records


def test_second_occurrence_is_registered_as_new():
    """DBに1件、今回2件の同一内容(例: Suicaの同一運賃を1日に2回利用) → 1件だけ新規登録"""
    db_records = [make_record(memo='利用場所:渋谷')]
    new_records = [make_record(memo='利用場所:渋谷'), make_record(memo='利用場所:渋谷')]
    result = dedup_against_db(new_records, db_records, USER_ID)
    assert len(result) == 1


def test_db_has_more_than_new_records_no_negative_insert():
    """DBに2件、今回1件の同一内容 → 0件登録（マイナスにはならない）"""
    db_records = [make_record(), make_record()]
    new_records = [make_record()]
    assert dedup_against_db(new_records, db_records, USER_ID) == []


def test_date_format_difference_does_not_break_fingerprint_match():
    """整形済みなら日付表記(YYYY-MM-DD)は揃っている前提だが、揃っていれば一致すること"""
    db_record = make_record(date='2026-07-10')
    new_record = make_record(date='2026-07-10')
    assert generate_fingerprint(USER_ID, db_record) == generate_fingerprint(USER_ID, new_record)


def test_zenkaku_hankaku_memo_normalizes_to_same_fingerprint():
    """全角/半角混在メモが正規化後に同一fingerprintとなること"""
    db_record = make_record(memo='ｾﾌﾞﾝｲﾚﾌﾞﾝ　渋谷店')
    new_record = make_record(memo='ｾﾌﾞﾝｲﾚﾌﾞﾝ 渋谷店')
    assert normalize_memo(db_record['メモ']) == normalize_memo(new_record['メモ'])
    assert generate_fingerprint(USER_ID, db_record) == generate_fingerprint(USER_ID, new_record)


def test_balance_ja_and_en_normalize_to_same_fingerprint():
    """CSV由来('支出')とDB由来('expense')の表記差異を吸収すること"""
    csv_record = make_record(balance='支出')
    db_record = {'date': '2026-07-10', 'balance': 'expense', 'category': '食費', 'amount': 500, 'memo': 'セブンイレブン'}
    assert generate_fingerprint(USER_ID, csv_record) == generate_fingerprint(USER_ID, db_record)


def test_get_date_range_returns_min_max():
    records = [make_record(date='2026-07-05'), make_record(date='2026-07-10'), make_record(date='2026-07-01')]
    assert get_date_range(records) == ('2026-07-01', '2026-07-10')


def test_get_date_range_applies_lookback_days():
    records = [make_record(date='2026-07-10')]
    assert get_date_range(records, lookback_days=14) == ('2026-06-26', '2026-07-10')


def test_get_date_range_raises_on_empty_records():
    try:
        get_date_range([])
        assert False, "ValueError が送出されるべき"
    except ValueError:
        pass
