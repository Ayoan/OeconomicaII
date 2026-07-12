import os
import sys
from unittest.mock import MagicMock, patch

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from scrapers.driver_factory import DEFAULT_DOWNLOAD_DIR, build_driver_factory  # noqa: E402


@patch('scrapers.driver_factory.webdriver.Chrome')
@patch('scrapers.driver_factory.webdriver.ChromeOptions')
def test_default_config_uses_headless_and_default_download_dir(mock_options_cls, mock_chrome_cls):
    mock_options = MagicMock()
    mock_options_cls.return_value = mock_options
    mock_driver = MagicMock()
    mock_chrome_cls.return_value = mock_driver

    driver_factory = build_driver_factory()
    driver_factory()

    mock_options.add_argument.assert_any_call('--headless=new')
    prefs_call = [c for c in mock_options.add_experimental_option.call_args_list if c.args[0] == 'prefs']
    assert prefs_call
    assert prefs_call[0].args[1]['download.default_directory'] == DEFAULT_DOWNLOAD_DIR
    mock_driver.execute_cdp_cmd.assert_called_once()


@patch('scrapers.driver_factory.webdriver.Chrome')
@patch('scrapers.driver_factory.webdriver.ChromeOptions')
def test_headless_false_does_not_add_headless_argument(mock_options_cls, mock_chrome_cls):
    mock_options = MagicMock()
    mock_options_cls.return_value = mock_options
    mock_chrome_cls.return_value = MagicMock()

    driver_factory = build_driver_factory({'HEADLESS': False})
    driver_factory()

    headless_calls = [c for c in mock_options.add_argument.call_args_list if c.args[0] == '--headless=new']
    assert headless_calls == []


@patch('scrapers.driver_factory.webdriver.Chrome')
@patch('scrapers.driver_factory.webdriver.ChromeOptions')
def test_custom_chrome_binary_is_set(mock_options_cls, mock_chrome_cls):
    mock_options = MagicMock()
    mock_options_cls.return_value = mock_options
    mock_chrome_cls.return_value = MagicMock()

    driver_factory = build_driver_factory({'CHROME_BINARY': '/snap/bin/chromium'})
    driver_factory()

    assert mock_options.binary_location == '/snap/bin/chromium'


@patch('scrapers.driver_factory.webdriver.Chrome')
@patch('scrapers.driver_factory.webdriver.ChromeOptions')
def test_custom_download_dir_is_used(mock_options_cls, mock_chrome_cls):
    mock_options = MagicMock()
    mock_options_cls.return_value = mock_options
    mock_chrome_cls.return_value = MagicMock()

    driver_factory = build_driver_factory({'DOWNLOAD_DIR': '/tmp/custom'})
    driver_factory()

    prefs_call = [c for c in mock_options.add_experimental_option.call_args_list if c.args[0] == 'prefs']
    assert prefs_call[0].args[1]['download.default_directory'] == '/tmp/custom'
