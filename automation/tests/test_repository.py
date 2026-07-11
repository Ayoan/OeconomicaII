import datetime
import os
import sys
from unittest.mock import MagicMock

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from db.repository import fetch_existing_records  # noqa: E402


def _make_mock_connection(rows):
    cursor = MagicMock()
    cursor.fetchall.return_value = rows
    cursor.__enter__.return_value = cursor
    cursor.__exit__.return_value = False

    connection = MagicMock()
    connection.cursor.return_value = cursor
    return connection, cursor


def test_fetch_existing_records_maps_columns_and_formats_date():
    rows = [
        {'date': datetime.date(2026, 7, 10), 'balance': 'expense', 'category': '食費', 'amount': 500, 'memo': 'セブン-イレブン'},
    ]
    connection, cursor = _make_mock_connection(rows)

    result = fetch_existing_records(1, '2026-07-01', '2026-07-10', connection=connection)

    assert result == [
        {'date': '2026-07-10', 'balance': 'expense', 'category': '食費', 'amount': 500, 'memo': 'セブン-イレブン'},
    ]


def test_fetch_existing_records_defaults_null_memo_to_empty_string():
    rows = [{'date': '2026-07-10', 'balance': 'expense', 'category': '食費', 'amount': 500, 'memo': None}]
    connection, cursor = _make_mock_connection(rows)

    result = fetch_existing_records(1, '2026-07-01', '2026-07-10', connection=connection)

    assert result[0]['memo'] == ''


def test_fetch_existing_records_does_not_close_injected_connection():
    connection, cursor = _make_mock_connection([])

    fetch_existing_records(1, '2026-07-01', '2026-07-10', connection=connection)

    connection.close.assert_not_called()


def test_fetch_existing_records_query_filters_by_user_and_date_range():
    connection, cursor = _make_mock_connection([])

    fetch_existing_records(1, '2026-07-01', '2026-07-10', connection=connection)

    args, kwargs = cursor.execute.call_args
    query, params = args
    assert 'user_id = %s' in query
    assert 'BETWEEN %s AND %s' in query
    assert params == (1, '2026-07-01', '2026-07-10')
