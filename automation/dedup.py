"""
重複排除モジュール

家計簿アプリ(OeconomicaII)の oeconomicas テーブルには外部取引IDが存在しないため、
フィンガープリント(SHA256) + 多重集合(Counter)方式で「DB未登録の新規レコードのみ」を
抽出する。同一内容の正当な複数取引(例: Suicaの同一運賃を1日に複数回利用)を
誤って除外しないよう、単純な集合の差分ではなく出現回数ベースで照合する。

設計書: 03_Home_Server/家計簿登録_AI効率化/Docs/設計書_家計簿登録AI効率化_システム設計.md 5章
"""

import hashlib
import os
import sys
from collections import Counter
from datetime import datetime, timedelta

sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'formatters'))
from csv_formatter_common import zenkaku_to_hankaku  # noqa: E402

BALANCE_JA_TO_EN = {'収入': 'income', '支出': 'expense'}


def normalize_memo(memo):
    """メモを正規化する（全角→半角、前後空白除去、連続スペース圧縮）

    Args:
        memo (str): 元のメモ文字列

    Returns:
        str: 正規化後のメモ文字列
    """
    if memo is None:
        return ''
    return zenkaku_to_hankaku(memo.strip()).strip()


def _normalize_balance(balance):
    """収支区分を英語表記(income/expense)へ正規化する"""
    return BALANCE_JA_TO_EN.get(balance, balance)


def _get(record, *keys):
    """複数の候補キー（日本語/英語）のいずれかで値を取得する"""
    for key in keys:
        if key in record:
            return record[key]
    raise KeyError(f"レコードに必須フィールドがありません: {keys}")


def generate_fingerprint(user_id, record):
    """レコードのフィンガープリント(SHA256)を生成する

    カテゴリは含めない。カテゴリはDB登録後にユーザーがアプリ上で手動修正する
    運用（「不明」カテゴリの修正等）が前提であり、fingerprintに含めると
    ユーザーが再分類した直後の再取得（特にメール通知方式はUID等の既読管理を
    持たず毎回LOOKBACK_DAYS分を再取得する設計のため）で同一取引が「新規」と
    誤判定され、重複登録される実害が発生した（2026-07-19、e-navi）。

    Args:
        user_id: ユーザーID
        record (dict): 共通スキーマ('日付','収支区分','カテゴリ','金額','メモ')
            またはDBスキーマ('date','balance','category','amount','memo')のレコード
            （'カテゴリ'/'category'キー自体はfingerprintの算出には使わない）

    Returns:
        str: SHA256ハッシュ値(hex文字列)
    """
    date = _get(record, '日付', 'date')
    balance = _normalize_balance(_get(record, '収支区分', 'balance'))
    amount = int(_get(record, '金額', 'amount'))
    memo = normalize_memo(_get(record, 'メモ', 'memo'))

    key = '|'.join([str(user_id), str(date), str(balance), str(amount), memo])
    return hashlib.sha256(key.encode('utf-8')).hexdigest()


def get_date_range(records, lookback_days=0):
    """レコード群から DB 照合に使う日付レンジ(min, max)を算出する

    Args:
        records (list[dict]): 共通スキーマのレコード群
        lookback_days (int): 開始日をさらに遡らせる日数（クレカ明細の確定ラグ対策）

    Returns:
        tuple[str, str]: (照合開始日, 照合終了日) いずれも 'YYYY-MM-DD'

    Raises:
        ValueError: records が空の場合
    """
    if not records:
        raise ValueError("records が空のため日付レンジを算出できません")

    dates = [_get(r, '日付', 'date') for r in records]
    min_date = min(dates)
    max_date = max(dates)

    if lookback_days:
        min_dt = datetime.strptime(min_date, '%Y-%m-%d') - timedelta(days=lookback_days)
        min_date = min_dt.strftime('%Y-%m-%d')

    return min_date, max_date


def dedup_against_db(new_records, db_records, user_id):
    """DB既存レコードとの突合を行い、新規登録すべきレコードのみを返す

    DB側のフィンガープリント出現回数を「在庫」とみなし、新規レコード側から
    1件ずつ消費する。在庫が尽きた分（＝DBの登録件数を上回った分）だけを
    新規登録対象とすることで、正当な同一内容の複数取引を保持したまま
    重複のみを除外する。

    Args:
        new_records (list[dict]): 今回取り込んだ共通スキーマのレコード群
        db_records (list[dict]): 照合対象期間の既存DBレコード群
        user_id: ユーザーID

    Returns:
        list[dict]: new_records のうち、DBに未登録と判定されたレコードのみ（元の順序を維持）
    """
    db_counter = Counter(generate_fingerprint(user_id, r) for r in db_records)
    to_insert = []

    for record in new_records:
        fp = generate_fingerprint(user_id, record)
        if db_counter[fp] > 0:
            db_counter[fp] -= 1
        else:
            to_insert.append(record)

    return to_insert
