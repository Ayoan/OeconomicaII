#!/usr/bin/env python3
"""
家計簿登録自動化パイプライン エントリポイント

(任意で)クレカサイトのスクレイピング(scrapers/*.py)で明細CSVを取得したうえで、
Formatter層(formatters/*.py)が出力済みの整形CSV(*_formatted.csv)を読み込み、
カテゴリ自動補完(category_mapper.py) → DB既存データとの重複排除(dedup.py) →
バリデーション(validation.py) → DB登録(db.repository.insert_records、プランA)
を行い、最終結果をLINE Botへ通知する(notify/line_bot.py)。
"""

import argparse
import csv
import glob
import json
import os
import shutil
from datetime import datetime

from category_mapper import apply_category_mapping, load_category_mapping
from dedup import dedup_against_db, get_date_range
from db.repository import (
    ensure_category_exists,
    fetch_existing_records,
    get_categories,
    insert_records,
)
from notify.line_bot import build_structure_error_message, build_summary_message, send_line_message
from validation import BALANCE_JA_TO_TYPE, validate_records

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DEFAULT_CONFIG_PATH = os.path.join(SCRIPT_DIR, 'config.json')
DEFAULT_MAPPING_PATH = os.path.join(SCRIPT_DIR, 'category_mapping.json')
DEFAULT_FORMATTED_GLOB = os.path.join(SCRIPT_DIR, 'formatters', 'datas', '*_formatted.csv')
PROCESSED_DIR = os.path.join(SCRIPT_DIR, 'formatters', 'datas', 'processed')


def archive_processed_csv(csv_paths, user_id, processed_dir=PROCESSED_DIR):
    """処理済みの整形CSVを formatters/datas/processed/ へ退避する

    formatters/datas/ 直下に処理済みファイルを置いたままにすると、次回実行時に
    別アカウント向けの処理が glob で誤って拾ってしまい、アカウント間でデータが
    混同する（2026-07-12、本番環境でSMBC(family)向けCSVがe-navi(naoya)実行時に
    誤登録される事故が実際に発生）。退避先ファイル名には user_id と時刻を付与し
    衝突を避ける。削除ではなく退避に留めるのは、将来 category_mapping.json の
    キーワードルールを拡充する際の実データとして再利用するため。

    Args:
        csv_paths (list[str]): run_pipeline() に渡した整形済みCSVファイルパスのリスト
        user_id: OeconomicaII のユーザーID（退避先ファイル名の衝突回避に使う）
        processed_dir (str): 退避先ディレクトリ（テスト時の差し替え用）
    """
    os.makedirs(processed_dir, exist_ok=True)
    timestamp = datetime.now().strftime('%H%M%S')
    for path in csv_paths:
        if not os.path.exists(path):
            continue
        name, ext = os.path.splitext(os.path.basename(path))
        dest = os.path.join(processed_dir, f'{name}_user{user_id}_{timestamp}{ext}')
        shutil.move(path, dest)


def load_formatted_records(csv_paths):
    """Formatter層が出力した整形済みCSV(複数)を共通スキーマのレコードリストへ統合する

    Args:
        csv_paths (list[str]): 整形済みCSVファイルパスのリスト

    Returns:
        list[dict]: 共通スキーマ('日付','収支区分','カテゴリ','金額','メモ')のレコード群
    """
    records = []
    for path in csv_paths:
        with open(path, 'r', encoding='utf-8', newline='') as f:
            for row in csv.DictReader(f):
                row['金額'] = int(row['金額'])
                records.append(row)
    return records


def run_pipeline(user_id, csv_paths, config_path=DEFAULT_CONFIG_PATH, mapping_path=DEFAULT_MAPPING_PATH,
                  fetch_existing=fetch_existing_records):
    """整形済みCSVの読み込みからDB重複排除までの一連の処理を実行する

    Args:
        user_id: OeconomicaII のユーザーID
        csv_paths (list[str]): 整形済みCSVファイルパスのリスト
        config_path (str): config.json のパス
        mapping_path (str): category_mapping.json のパス
        fetch_existing: DB照会関数（テスト時にモックを注入するためのフック）

    Returns:
        list[dict]: 新規登録対象レコード(to_insert)。登録データが無ければ空リスト
    """
    with open(config_path, 'r', encoding='utf-8') as f:
        config = json.load(f)
    lookback_days = config.get('DEDUP_CONFIG', {}).get('LOOKBACK_DAYS', 14)

    records = load_formatted_records(csv_paths)
    if not records:
        return []

    mapping = load_category_mapping(mapping_path)
    records = apply_category_mapping(records, mapping)

    start_date, end_date = get_date_range(records, lookback_days=lookback_days)
    db_records = fetch_existing(user_id, start_date, end_date)

    return dedup_against_db(records, db_records, user_id)


def register_records(user_id, to_insert):
    """dedup結果(to_insert)をバリデーションの上でDBへ登録する（登録層・プランA）

    未知のカテゴリ（category_mapping.jsonのdefault_category「不明」等）は
    ensure_category_exists() で事前登録してから validate_records() へ渡す
    （設計書9.3節）。

    Args:
        user_id: OeconomicaII のユーザーID
        to_insert (list[dict]): run_pipeline() の戻り値（重複排除後のレコード群）

    Returns:
        dict: {'inserted': int, 'errors': list[str]}
    """
    if not to_insert:
        return {'inserted': 0, 'errors': []}

    needed_categories = {
        (r['カテゴリ'], BALANCE_JA_TO_TYPE.get(r['収支区分']))
        for r in to_insert
    }
    for category, balance_type in needed_categories:
        if balance_type is not None:
            ensure_category_exists(user_id, category, balance_type)

    categories = get_categories(user_id)
    valid_records, errors = validate_records(to_insert, categories)
    inserted = insert_records(user_id, valid_records)

    return {'inserted': inserted, 'errors': errors}


def notify_summary(config, inserted, errors, skipped_sources=None):
    """実行結果サマリをLINEへ通知する

    新規登録0件・エラーなし・取得失敗なし（＝何も起きなかった）場合は通知しない。
    登録0件でもエラーやスクレイピング失敗があれば通知する。
    送信自体の失敗はパイプラインを止めない。
    """
    if inserted == 0 and not errors and not skipped_sources:
        print("新規登録・エラーともになし。LINE通知はスキップします。")
        return

    message = build_summary_message(inserted, errors, skipped_sources)
    try:
        send_line_message(message, config)
    except Exception as exc:
        print(f"LINE通知に失敗しました（処理は継続します）: {exc}")


def main():
    parser = argparse.ArgumentParser(description='家計簿登録自動化パイプライン（スクレイピング〜整形〜重複排除〜DB登録〜LINE通知）')
    parser.add_argument('--user-id', type=int, required=True, help='OeconomicaII のユーザーID')
    parser.add_argument('--csv', nargs='*', help='整形済みCSVファイルパス（省略時は formatters/datas/*_formatted.csv を自動検出）')
    parser.add_argument('--config', default=DEFAULT_CONFIG_PATH)
    parser.add_argument('--mapping', default=DEFAULT_MAPPING_PATH)
    parser.add_argument('--scrape', action='store_true', help='実行前にクレカサイトのスクレイピングを行う（要selenium・chromedriver）')
    parser.add_argument('--email', action='store_true', help='実行前にSMBCカードのご利用通知メール（IMAP）を取得・整形する')
    args = parser.parse_args()

    with open(args.config, 'r', encoding='utf-8') as f:
        config = json.load(f)

    skipped_sources = []
    if args.scrape:
        from scrapers.driver_factory import build_driver_factory
        from scrapers.run_scrapers import run_all_scrapers

        driver_factory = build_driver_factory(config.get('SCRAPER_CONFIG'))
        skipped_sources = run_all_scrapers(config, driver_factory=driver_factory)

        if 'e-navi' not in skipped_sources and config.get('CREDIT_CARDS', {}).get('E_NAVI'):
            from formatters.format_rakuten_csv import format_rakuten_csv

            format_rakuten_csv()

    if args.email:
        email_config = config.get('EMAIL_SOURCES', {}).get('SMBC_NOTIFICATION')
        if email_config:
            from email_fetcher.imap_client import EmailFetchError
            from formatters.format_smbc_email import format_smbc_email

            try:
                format_smbc_email(email_config)
            except EmailFetchError as exc:
                skipped_sources.append('SMBC(メール通知)')
                message = build_structure_error_message('SMBC(メール通知)', str(exc))
                try:
                    send_line_message(message, config)
                except Exception as notify_exc:
                    print(f"LINE通知に失敗しました（処理は継続します）: {notify_exc}")

    used_default_glob = not args.csv
    csv_paths = args.csv if args.csv else sorted(glob.glob(DEFAULT_FORMATTED_GLOB))
    if not csv_paths:
        print("整形済みCSVが見つかりません。先に formatters/ 配下の各スクリプトを実行してください。")
        notify_summary(config, inserted=0, errors=[], skipped_sources=skipped_sources)
        return

    to_insert = run_pipeline(args.user_id, csv_paths, args.config, args.mapping)
    result = register_records(args.user_id, to_insert)

    print(f"新規登録: {result['inserted']}件")
    if result['errors']:
        print(f"バリデーションエラー: {len(result['errors'])}件")
        for error in result['errors']:
            print(f"  - {error}")

    # formatters/datas/ 直下の自動検出分のみ退避する（--csv 明示指定時は
    # 呼び出し側の管理下にあるファイルのため移動しない）
    if used_default_glob:
        archive_processed_csv(csv_paths, args.user_id)

    notify_summary(config, result['inserted'], result['errors'], skipped_sources)


if __name__ == '__main__':
    main()
