"""
スクレイパー実行オーケストレーション

複数のクレジットカードサイトを順に処理し、失敗したデータソースがあっても
他のデータソースの処理は継続する。失敗時は即座にLINE通知する
（CLAUDE.md 3章の例外処理フロー: 失敗 → LINE通知 → ユーザーがXPATH等を修正）。
"""

import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from notify.line_bot import build_structure_error_message, send_line_message  # noqa: E402
from scrapers.base_scraper import ScraperError  # noqa: E402
from scrapers.enavi_scraper import ENaviScraper  # noqa: E402
from scrapers.smbc_scraper import SMBCScraper  # noqa: E402

def run_all_scrapers(config, driver_factory, notify=send_line_message):
    """config.json の CREDIT_CARDS に定義された全データソースを処理する

    Args:
        config (dict): config.json の内容
        driver_factory (callable): Selenium WebDriverインスタンスを返す関数
        notify (callable): LINE通知関数（テスト時にモックを注入するためのフック）

    Returns:
        list[str]: 取得に失敗したデータソース名のリスト（成功時は空リスト）
    """
    # CREDIT_CARDS のキー -> スクレイパークラス（呼び出し時に解決することで、
    # テスト時に module-level のクラス参照をモック差し替えできるようにする）
    scraper_registry = {
        'E_NAVI': ENaviScraper,
        'SMBC': SMBCScraper,
    }

    skipped_sources = []

    for card_key, scraper_cls in scraper_registry.items():
        card_config = config.get('CREDIT_CARDS', {}).get(card_key)
        if not card_config:
            continue

        scraper = scraper_cls(card_config, driver_factory)
        try:
            scraper.download_csv()
        except ScraperError as exc:
            skipped_sources.append(scraper_cls.SOURCE_NAME)
            message = build_structure_error_message(scraper_cls.SOURCE_NAME, str(exc))
            notify(message, config)

    return skipped_sources
