"""SMBCカード Web明細CSVダウンロードスクレイパー"""

import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scrapers.base_scraper import BaseScraper  # noqa: E402


class SMBCScraper(BaseScraper):
    SOURCE_NAME = 'SMBC'
