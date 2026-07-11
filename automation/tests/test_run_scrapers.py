import os
import sys
from unittest.mock import MagicMock, patch

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from scrapers.base_scraper import StructureChangedError  # noqa: E402
from scrapers.run_scrapers import run_all_scrapers  # noqa: E402

CONFIG = {
    'LINE_BOT_CONFIG': {'CHANNEL_ACCESS_TOKEN': 'tok', 'USER_ID': 'user'},
    'CREDIT_CARDS': {
        'E_NAVI': {'LOGIN_URL': 'https://enavi.example', 'USER_ID': 'u', 'PASSWORD': 'p',
                   'XPATH_USERNAME_INPUT': '//a', 'XPATH_PASSWORD_INPUT': '//b',
                   'XPATH_LOGIN_BUTTON': '//c', 'XPATH_DOWNLOAD_BUTTON': '//d'},
        'SMBC': {'LOGIN_URL': 'https://smbc.example', 'USER_ID': 'u', 'PASSWORD': 'p',
                 'XPATH_USERNAME_INPUT': '//a', 'XPATH_PASSWORD_INPUT': '//b',
                 'XPATH_LOGIN_BUTTON': '//c', 'XPATH_DOWNLOAD_BUTTON': '//d'},
    },
}


def test_run_all_scrapers_returns_empty_list_when_all_succeed():
    with patch('scrapers.run_scrapers.ENaviScraper') as mock_enavi, \
         patch('scrapers.run_scrapers.SMBCScraper') as mock_smbc:
        mock_enavi.return_value.download_csv.return_value = None
        mock_enavi.SOURCE_NAME = 'e-navi'
        mock_smbc.return_value.download_csv.return_value = None
        mock_smbc.SOURCE_NAME = 'SMBC'

        notify = MagicMock()
        skipped = run_all_scrapers(CONFIG, driver_factory=lambda: MagicMock(), notify=notify)

    assert skipped == []
    notify.assert_not_called()


def test_run_all_scrapers_continues_after_one_source_fails():
    with patch('scrapers.run_scrapers.ENaviScraper') as mock_enavi, \
         patch('scrapers.run_scrapers.SMBCScraper') as mock_smbc:
        mock_enavi.return_value.download_csv.side_effect = StructureChangedError('//d')
        mock_enavi.return_value.SOURCE_NAME = 'e-navi'
        mock_enavi.SOURCE_NAME = 'e-navi'
        mock_smbc.return_value.download_csv.return_value = None
        mock_smbc.SOURCE_NAME = 'SMBC'

        notify = MagicMock()
        skipped = run_all_scrapers(CONFIG, driver_factory=lambda: MagicMock(), notify=notify)

    assert skipped == ['e-navi']
    mock_smbc.return_value.download_csv.assert_called_once()  # 他のソースは継続実行される
    notify.assert_called_once()
    message_arg = notify.call_args.args[0]
    assert 'e-navi' in message_arg


def test_run_all_scrapers_skips_source_without_config():
    config = {'LINE_BOT_CONFIG': CONFIG['LINE_BOT_CONFIG'], 'CREDIT_CARDS': {'E_NAVI': CONFIG['CREDIT_CARDS']['E_NAVI']}}

    with patch('scrapers.run_scrapers.ENaviScraper') as mock_enavi, \
         patch('scrapers.run_scrapers.SMBCScraper') as mock_smbc:
        mock_enavi.return_value.download_csv.return_value = None

        notify = MagicMock()
        skipped = run_all_scrapers(config, driver_factory=lambda: MagicMock(), notify=notify)

    assert skipped == []
    mock_smbc.assert_not_called()
