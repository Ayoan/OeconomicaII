"""
登録前バリデーション

プランA(DB直接INSERT)採用に伴い、HouseholdController::importCsv() が
Webフォーム経由で行っているチェック（カテゴリ存在確認・金額範囲・日付形式）を
automation側で同等に実装する。ここを通過したレコードのみ db.repository.insert_records() へ渡す。
"""

from datetime import datetime

BALANCE_JA_TO_TYPE = {'収入': 'income', '支出': 'expense'}


def _is_valid_date(date_str):
    try:
        datetime.strptime(date_str, '%Y-%m-%d')
        return True
    except (TypeError, ValueError):
        return False


def validate_records(records, categories):
    """レコード群を検証し、有効なものとエラーに分けて返す

    Args:
        records (list[dict]): 共通スキーマ('日付','収支区分','カテゴリ','金額','メモ')のレコード群
        categories (dict): db.repository.get_categories() の戻り値
            {'income': [...], 'expense': [...]}（登録前に ensure_category_exists で
            default_category を追加済みであること）

    Returns:
        tuple[list[dict], list[str]]: (有効なレコード群, エラーメッセージ群)
    """
    valid_records = []
    errors = []

    for record in records:
        date = record.get('日付')
        balance = record.get('収支区分')
        category = record.get('カテゴリ')
        amount = record.get('金額')
        memo = record.get('メモ', '')

        if not _is_valid_date(date):
            errors.append(f"日付形式が正しくありません（{date}）: {memo}")
            continue

        balance_type = BALANCE_JA_TO_TYPE.get(balance)
        if balance_type is None:
            errors.append(f"収支区分は「収入」または「支出」で指定してください（{balance}）: {memo}")
            continue

        if category not in categories.get(balance_type, []):
            errors.append(f"カテゴリ「{category}」は{balance}カテゴリに存在しません: {memo}")
            continue

        try:
            amount_int = int(amount)
        except (TypeError, ValueError):
            amount_int = None

        if amount_int is None or amount_int < 1:
            errors.append(f"金額は1以上の数値で指定してください（{amount}）: {memo}")
            continue

        valid_records.append(record)

    return valid_records, errors
