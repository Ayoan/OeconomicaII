import os
import sys
from unittest.mock import MagicMock

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from scrapers.base_scraper import BaseScraper, LoginFailedError, StructureChangedError  # noqa: E402

CARD_CONFIG = {
    'LOGIN_URL': 'https://example.com/login',
    'USER_ID': 'user1',
    'PASSWORD': 'pass1',
    'XPATH_USERNAME_INPUT': '//input[@id="username"]',
    'XPATH_PASSWORD_INPUT': '//input[@id="password"]',
    'XPATH_LOGIN_BUTTON': '//button[@id="login"]',
    'XPATH_DOWNLOAD_BUTTON': '//button[@id="download"]',
}


def _make_driver(find_element_side_effect=None):
    driver = MagicMock()
    if find_element_side_effect is not None:
        driver.find_element.side_effect = find_element_side_effect
    return driver


def test_download_csv_happy_path_fills_login_form_and_clicks_download():
    driver = _make_driver()
    scraper = BaseScraper(CARD_CONFIG, driver_factory=lambda: driver)

    scraper.download_csv()

    driver.get.assert_called_once_with(CARD_CONFIG['LOGIN_URL'])
    # username, password, login button, download button
    assert driver.find_element.call_count == 4
    driver.quit.assert_called_once()


def test_download_csv_calls_download_button_after_login():
    driver = _make_driver()
    scraper = BaseScraper(CARD_CONFIG, driver_factory=lambda: driver)

    scraper.download_csv()

    xpaths_used = [call.args[1] for call in driver.find_element.call_args_list]
    assert CARD_CONFIG['XPATH_DOWNLOAD_BUTTON'] in xpaths_used


def test_missing_element_raises_structure_changed_error():
    driver = _make_driver(find_element_side_effect=Exception('no such element'))
    scraper = BaseScraper(CARD_CONFIG, driver_factory=lambda: driver)

    try:
        scraper.download_csv()
        assert False, "StructureChangedError が送出されるべき"
    except StructureChangedError as e:
        assert CARD_CONFIG['XPATH_USERNAME_INPUT'] == e.xpath

    driver.quit.assert_called_once()  # 例外時もquitされること


def test_login_error_indicator_raises_login_failed_error():
    config = dict(CARD_CONFIG, XPATH_LOGIN_ERROR_INDICATOR='//div[@class="error"]')
    driver = _make_driver()
    error_element = MagicMock()
    error_element.text = 'IDまたはパスワードが違います'

    def find_element_side_effect(by, xpath):
        if xpath == config['XPATH_LOGIN_ERROR_INDICATOR']:
            return error_element
        return MagicMock()

    driver.find_element.side_effect = find_element_side_effect
    scraper = BaseScraper(config, driver_factory=lambda: driver)

    try:
        scraper.download_csv()
        assert False, "LoginFailedError が送出されるべき"
    except LoginFailedError as e:
        assert 'IDまたはパスワードが違います' in str(e)


def test_login_error_indicator_not_found_means_success():
    config = dict(CARD_CONFIG, XPATH_LOGIN_ERROR_INDICATOR='//div[@class="error"]')
    driver = _make_driver()

    def find_element_side_effect(by, xpath):
        if xpath == config['XPATH_LOGIN_ERROR_INDICATOR']:
            raise Exception('not found')
        return MagicMock()

    driver.find_element.side_effect = find_element_side_effect
    scraper = BaseScraper(config, driver_factory=lambda: driver)

    scraper.download_csv()  # 例外が出ないこと

    driver.quit.assert_called_once()
