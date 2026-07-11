"""三井住友カード e-navi の明細CSVダウンロードスクレイパー"""

import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from base_scraper import BaseScraper  # noqa: E402


class ENaviScraper(BaseScraper):
    SOURCE_NAME = 'e-navi'
