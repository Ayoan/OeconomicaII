"""
OeconomicaII DBアクセス層

重複排除(dedup.py)の照合に必要な既存レコードの取得(照会)、および
新規レコードの登録(INSERT)を担当する。

登録方式は「プランA: DB直接INSERT」を採用（プランB: OeconomicaII CSVインポート
Webエンドポイント利用は、セッション認証+CSRFトークンが必須でcron実行に不向きなため
不採用。設計書9章参照）。Laravel側のバリデーション（カテゴリ存在チェック等）を
経由しないため、validation.py で同等のチェックを自前実装している。

接続情報は OeconomicaII リポジトリ直下の .env (DB_*) をそのまま利用し、
automation側で認証情報を二重管理しない。

設計書: 03_Home_Server/家計簿登録_AI効率化/Docs/設計書_家計簿登録AI効率化_システム設計.md 9章
"""

import os
import sys

import pymysql
from dotenv import load_dotenv
from pymysql.cursors import DictCursor

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from dedup import BALANCE_JA_TO_EN  # noqa: E402

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
ENV_PATH = os.path.join(REPO_ROOT, '.env')

# 「不明」カテゴリ新規作成時の既定色（他のデフォルトカテゴリと視覚的に区別できるグレー）
UNCLASSIFIED_CATEGORY_COLOR = '#9CA3AF'


def get_connection():
    """OeconomicaII の .env (DB_*) を読み込み、DBへ接続する

    docker-compose.yml で DB_PORT がホストへ公開されている前提
    （06_Github/OeconomicaII/docker-compose.yml 参照）。automation は
    OeconomicaII と同一ホスト上での実行を想定し、host は環境変数
    DB_AUTOMATION_HOST（未設定時 'localhost'）を用いる。

    Returns:
        pymysql.connections.Connection
    """
    load_dotenv(ENV_PATH)
    return pymysql.connect(
        host=os.environ.get('DB_AUTOMATION_HOST', 'localhost'),
        port=int(os.environ.get('DB_PORT', 3306)),
        user=os.environ['DB_USERNAME'],
        password=os.environ['DB_PASSWORD'],
        database=os.environ['DB_DATABASE'],
        charset='utf8mb4',
        cursorclass=DictCursor,
    )


def fetch_existing_records(user_id, start_date, end_date, connection=None):
    """指定ユーザー・期間の既存 oeconomicas レコードを取得する（重複排除の照合用）

    Args:
        user_id: ユーザーID
        start_date (str): 'YYYY-MM-DD'（照合開始日。dedup.get_date_range の戻り値を想定）
        end_date (str): 'YYYY-MM-DD'
        connection: 既存のDB接続。省略時は get_connection() で新規接続し、
            呼び出し終了時にクローズする（テスト時にモック接続を注入する用途）

    Returns:
        list[dict]: [{'date': 'YYYY-MM-DD', 'balance': 'income'/'expense',
                       'category': str, 'amount': int, 'memo': str}, ...]
    """
    owns_connection = connection is None
    conn = connection or get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute(
                """
                SELECT date, balance, category, amount, memo
                FROM oeconomicas
                WHERE user_id = %s AND date BETWEEN %s AND %s
                """,
                (user_id, start_date, end_date),
            )
            rows = cursor.fetchall()

        return [
            {
                'date': row['date'].strftime('%Y-%m-%d') if hasattr(row['date'], 'strftime') else row['date'],
                'balance': row['balance'],
                'category': row['category'],
                'amount': row['amount'],
                'memo': row['memo'] or '',
            }
            for row in rows
        ]
    finally:
        if owns_connection:
            conn.close()


def get_categories(user_id, connection=None):
    """指定ユーザーのカテゴリ一覧を income/expense 別に取得する

    HouseholdController::importCsv() のカテゴリ検証ロジックと同等の情報源。

    Args:
        user_id: ユーザーID
        connection: 既存のDB接続（省略時は新規接続してクローズする）

    Returns:
        dict: {'income': ['給与', ...], 'expense': ['食費', ...]}
    """
    owns_connection = connection is None
    conn = connection or get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute(
                "SELECT category, type FROM categories WHERE user_id = %s",
                (user_id,),
            )
            rows = cursor.fetchall()

        categories = {'income': [], 'expense': []}
        for row in rows:
            categories[row['type']].append(row['category'])
        return categories
    finally:
        if owns_connection:
            conn.close()


def ensure_category_exists(user_id, category, type_, color=UNCLASSIFIED_CATEGORY_COLOR, connection=None):
    """指定カテゴリが categories テーブルに存在しなければ作成する（冪等）

    category_mapping.json の default_category（「不明」）は OeconomicaII の
    デフォルトカテゴリに含まれないため、プランA(直接INSERT)採用に伴い
    登録前提として本関数で事前登録する（設計書9.3節）。

    Args:
        user_id: ユーザーID
        category (str): カテゴリ名
        type_ (str): 'income' または 'expense'
        color (str): 新規作成時の色（#RRGGBB）
        connection: 既存のDB接続（省略時は新規接続してクローズする）
    """
    owns_connection = connection is None
    conn = connection or get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute(
                "SELECT id FROM categories WHERE user_id = %s AND category = %s AND type = %s",
                (user_id, category, type_),
            )
            if cursor.fetchone():
                return

            cursor.execute(
                "INSERT INTO categories (user_id, category, type, color, sort_order, created_at, updated_at) "
                "VALUES (%s, %s, %s, %s, 0, NOW(), NOW())",
                (user_id, category, type_, color),
            )
        if owns_connection:
            conn.commit()
    finally:
        if owns_connection:
            conn.close()


def insert_records(user_id, records, connection=None):
    """新規レコードを oeconomicas テーブルへ一括登録する

    Args:
        user_id: ユーザーID
        records (list[dict]): 共通スキーマ('日付','収支区分','カテゴリ','金額','メモ')の
            レコード群。事前に validation.validate_records() を通し、
            ensure_category_exists() でカテゴリ存在を保証しておくこと
        connection: 既存のDB接続（省略時は新規接続してクローズする）

    Returns:
        int: 登録件数
    """
    if not records:
        return 0

    owns_connection = connection is None
    conn = connection or get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.executemany(
                "INSERT INTO oeconomicas (user_id, balance, date, category, amount, memo, created_at, updated_at) "
                "VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())",
                [
                    (
                        user_id,
                        BALANCE_JA_TO_EN.get(r['収支区分'], r['収支区分']),
                        r['日付'],
                        r['カテゴリ'],
                        int(r['金額']),
                        r.get('メモ', ''),
                    )
                    for r in records
                ],
            )
        if owns_connection:
            conn.commit()
        return len(records)
    finally:
        if owns_connection:
            conn.close()
