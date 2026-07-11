import os
import sys
from unittest.mock import MagicMock

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from db.repository import ensure_category_exists, get_categories, insert_records  # noqa: E402


def _make_mock_connection(fetchone_result=None, fetchall_result=None):
    cursor = MagicMock()
    cursor.fetchone.return_value = fetchone_result
    cursor.fetchall.return_value = fetchall_result or []
    cursor.__enter__.return_value = cursor
    cursor.__exit__.return_value = False

    connection = MagicMock()
    connection.cursor.return_value = cursor
    return connection, cursor


def test_get_categories_groups_by_type():
    rows = [
        {'category': '食費', 'type': 'expense'},
        {'category': '給与', 'type': 'income'},
        {'category': '日用品', 'type': 'expense'},
    ]
    connection, cursor = _make_mock_connection(fetchall_result=rows)

    result = get_categories(1, connection=connection)

    assert result == {'income': ['給与'], 'expense': ['食費', '日用品']}


def test_ensure_category_exists_skips_insert_when_already_present():
    connection, cursor = _make_mock_connection(fetchone_result={'id': 1})

    ensure_category_exists(1, '不明', 'expense', connection=connection)

    insert_calls = [c for c in cursor.execute.call_args_list if 'INSERT INTO categories' in c.args[0]]
    assert insert_calls == []


def test_ensure_category_exists_inserts_when_missing():
    connection, cursor = _make_mock_connection(fetchone_result=None)

    ensure_category_exists(1, '不明', 'expense', connection=connection)

    insert_calls = [c for c in cursor.execute.call_args_list if 'INSERT INTO categories' in c.args[0]]
    assert len(insert_calls) == 1
    params = insert_calls[0].args[1]
    assert params[:3] == (1, '不明', 'expense')


def test_ensure_category_exists_does_not_commit_injected_connection():
    connection, cursor = _make_mock_connection(fetchone_result=None)

    ensure_category_exists(1, '不明', 'expense', connection=connection)

    connection.commit.assert_not_called()


def test_insert_records_builds_expected_rows():
    connection, cursor = _make_mock_connection()
    records = [
        {'日付': '2026-07-10', '収支区分': '支出', 'カテゴリ': '食費', '金額': 500, 'メモ': 'セブン'},
    ]

    count = insert_records(1, records, connection=connection)

    assert count == 1
    args, kwargs = cursor.executemany.call_args
    query, rows = args
    assert 'INSERT INTO oeconomicas' in query
    assert rows == [(1, 'expense', '2026-07-10', '食費', 500, 'セブン')]


def test_insert_records_returns_zero_for_empty_list():
    connection, cursor = _make_mock_connection()

    count = insert_records(1, [], connection=connection)

    assert count == 0
    cursor.executemany.assert_not_called()
