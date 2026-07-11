import os
import sys
from datetime import date
from unittest.mock import MagicMock, patch

import pytest

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from selenium.common.exceptions import ElementClickInterceptedException  # noqa: E402

from scrapers.base_scraper import BaseScraper, LoginFailedError, StructureChangedError  # noqa: E402


@pytest.fixture(autouse=True)
def _no_real_sleep():
    """_wait_for_download() の実待機(既定5秒)でテストが遅くならないようにする"""
    with patch('scrapers.base_scraper.time.sleep'):
        yield

CARD_CONFIG = {
    'LOGIN_URL': 'https://example.com/login',
    'USER_ID': 'user1',
    'PASSWORD': 'pass1',
    'XPATH_USERNAME_INPUT': '//input[@id="username"]',
    'XPATH_PASSWORD_INPUT': '//input[@id="password"]',
    'XPATH_LOGIN_BUTTON': '//button[@id="login"]',
    'XPATH_DOWNLOAD_BUTTON': '//button[@id="download"]',
}


def _visible_element():
    """element_to_be_clickable の判定(is_displayed()==True, is_enabled())を満たすモック要素"""
    element = MagicMock()
    element.is_displayed.return_value = True
    element.is_enabled.return_value = True
    return element


def _make_driver(find_element_side_effect=None):
    driver = MagicMock()
    driver.find_element.return_value = _visible_element()
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
        return _visible_element()

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
        return _visible_element()

    driver.find_element.side_effect = find_element_side_effect
    scraper = BaseScraper(config, driver_factory=lambda: driver)

    scraper.download_csv()  # 例外が出ないこと

    driver.quit.assert_called_once()


TWO_STEP_CARD_CONFIG = dict(CARD_CONFIG, XPATH_NEXT_BUTTON='//button[@id="next"]')


def test_two_step_login_clicks_next_button_between_username_and_password():
    driver = _make_driver()
    scraper = BaseScraper(TWO_STEP_CARD_CONFIG, driver_factory=lambda: driver)

    scraper.download_csv()

    xpaths_used = [call.args[1] for call in driver.find_element.call_args_list]
    assert xpaths_used == [
        TWO_STEP_CARD_CONFIG['XPATH_USERNAME_INPUT'],
        TWO_STEP_CARD_CONFIG['XPATH_NEXT_BUTTON'],
        TWO_STEP_CARD_CONFIG['XPATH_PASSWORD_INPUT'],
        TWO_STEP_CARD_CONFIG['XPATH_LOGIN_BUTTON'],
        TWO_STEP_CARD_CONFIG['XPATH_DOWNLOAD_BUTTON'],
    ]


@patch('scrapers.base_scraper.time.sleep')
def test_pre_login_click_delay_waits_before_login_button(mock_sleep):
    """機械的な連続操作に見えないよう、設定時はパスワード入力後・ログインボタン押下前に待機すること"""
    config = dict(CARD_CONFIG, PRE_LOGIN_CLICK_DELAY_SECONDS=3)
    driver = _make_driver()
    scraper = BaseScraper(config, driver_factory=lambda: driver)

    scraper.download_csv()

    mock_sleep.assert_any_call(3)


def test_no_pre_login_click_delay_by_default():
    driver = _make_driver()
    scraper = BaseScraper(CARD_CONFIG, driver_factory=lambda: driver)  # PRE_LOGIN_CLICK_DELAY_SECONDS未設定

    with patch('scrapers.base_scraper.time.sleep') as mock_sleep:
        scraper.download_csv()

    # _wait_for_download()分の呼び出しのみで、ログイン前の待機は発生しないこと
    assert mock_sleep.call_count == 1


def test_one_step_login_does_not_look_for_next_button():
    driver = _make_driver()
    scraper = BaseScraper(CARD_CONFIG, driver_factory=lambda: driver)  # XPATH_NEXT_BUTTON未設定

    scraper.download_csv()

    xpaths_used = [call.args[1] for call in driver.find_element.call_args_list]
    assert CARD_CONFIG.get('XPATH_NEXT_BUTTON') not in xpaths_used
    assert 'XPATH_NEXT_BUTTON' not in CARD_CONFIG


def test_missing_next_button_raises_structure_changed_error():
    def find_element_side_effect(by, xpath):
        if xpath == TWO_STEP_CARD_CONFIG['XPATH_NEXT_BUTTON']:
            raise Exception('no such element')
        return _visible_element()

    driver = _make_driver(find_element_side_effect=find_element_side_effect)
    scraper = BaseScraper(TWO_STEP_CARD_CONFIG, driver_factory=lambda: driver)

    try:
        scraper.download_csv()
        assert False, "StructureChangedError が送出されるべき"
    except StructureChangedError as e:
        assert e.xpath == TWO_STEP_CARD_CONFIG['XPATH_NEXT_BUTTON']


MULTI_MONTH_CARD_CONFIG = dict(
    CARD_CONFIG,
    XPATH_MONTH_DROPDOWN='//select[@id="month"]',
    XPATH_MONTH_SEARCH_BUTTON='//button[@id="search"]',
    ADDITIONAL_MONTH_OFFSETS=[1],
)


def test_target_month_text_computes_next_month():
    scraper = BaseScraper(MULTI_MONTH_CARD_CONFIG, driver_factory=lambda: MagicMock(),
                           today_func=lambda: date(2026, 7, 11))
    assert scraper._target_month_text(1) == '2026年8月'


def test_target_month_text_handles_year_rollover():
    scraper = BaseScraper(MULTI_MONTH_CARD_CONFIG, driver_factory=lambda: MagicMock(),
                           today_func=lambda: date(2026, 12, 15))
    assert scraper._target_month_text(1) == '2027年1月'


@patch('scrapers.base_scraper.Select')
def test_download_csv_with_additional_month_offset_downloads_twice(mock_select_cls):
    driver = _make_driver()
    scraper = BaseScraper(MULTI_MONTH_CARD_CONFIG, driver_factory=lambda: driver,
                           today_func=lambda: date(2026, 7, 11))

    scraper.download_csv()

    xpaths_used = [call.args[1] for call in driver.find_element.call_args_list]
    # XPATH_DOWNLOAD_BUTTON_ADDITIONAL未設定時は、翌月分もXPATH_DOWNLOAD_BUTTONを使う
    assert xpaths_used.count(MULTI_MONTH_CARD_CONFIG['XPATH_DOWNLOAD_BUTTON']) == 2
    assert MULTI_MONTH_CARD_CONFIG['XPATH_MONTH_DROPDOWN'] in xpaths_used
    assert MULTI_MONTH_CARD_CONFIG['XPATH_MONTH_SEARCH_BUTTON'] in xpaths_used

    mock_select_cls.return_value.select_by_visible_text.assert_called_once_with('2026年8月')


@patch('scrapers.base_scraper.Select')
def test_download_csv_uses_distinct_button_for_additional_month(mock_select_cls):
    """SMBCのようにデフォルト表示画面と月切替後の画面でダウンロードボタンのXPATHが
    異なる場合、XPATH_DOWNLOAD_BUTTON_ADDITIONAL を使う"""
    config = dict(MULTI_MONTH_CARD_CONFIG, XPATH_DOWNLOAD_BUTTON_ADDITIONAL='//button[@id="download-next"]')
    driver = _make_driver()
    scraper = BaseScraper(config, driver_factory=lambda: driver, today_func=lambda: date(2026, 7, 11))

    scraper.download_csv()

    xpaths_used = [call.args[1] for call in driver.find_element.call_args_list]
    assert xpaths_used.count(config['XPATH_DOWNLOAD_BUTTON']) == 1
    assert xpaths_used.count(config['XPATH_DOWNLOAD_BUTTON_ADDITIONAL']) == 1


@patch('scrapers.base_scraper.Select')
def test_month_dropdown_selection_failure_raises_structure_changed_error(mock_select_cls):
    mock_select_cls.return_value.select_by_visible_text.side_effect = Exception('option not found')
    driver = _make_driver()
    scraper = BaseScraper(MULTI_MONTH_CARD_CONFIG, driver_factory=lambda: driver,
                           today_func=lambda: date(2026, 7, 11))

    try:
        scraper.download_csv()
        assert False, "StructureChangedError が送出されるべき"
    except StructureChangedError as e:
        assert e.xpath == MULTI_MONTH_CARD_CONFIG['XPATH_MONTH_DROPDOWN']


def test_no_additional_offsets_downloads_only_once():
    driver = _make_driver()
    scraper = BaseScraper(CARD_CONFIG, driver_factory=lambda: driver)  # ADDITIONAL_MONTH_OFFSETS未設定

    scraper.download_csv()

    xpaths_used = [call.args[1] for call in driver.find_element.call_args_list]
    assert xpaths_used.count(CARD_CONFIG['XPATH_DOWNLOAD_BUTTON']) == 1


def test_statement_url_navigates_after_login():
    """ログイン後にトップページ等へ遷移するサイト(e-navi)向けに、
    STATEMENT_URLが設定されていれば明細ページへ直接遷移すること"""
    config = dict(CARD_CONFIG, STATEMENT_URL='https://example.com/statement')
    driver = _make_driver()
    scraper = BaseScraper(config, driver_factory=lambda: driver)

    scraper.download_csv()

    driver.get.assert_any_call(config['LOGIN_URL'])
    driver.get.assert_any_call(config['STATEMENT_URL'])
    # get(LOGIN_URL) → ログイン → get(STATEMENT_URL) → ダウンロード、の順で呼ばれること
    get_calls = [call.args[0] for call in driver.get.call_args_list]
    assert get_calls == [config['LOGIN_URL'], config['STATEMENT_URL']]


def test_no_statement_url_does_not_navigate_again():
    driver = _make_driver()
    scraper = BaseScraper(CARD_CONFIG, driver_factory=lambda: driver)  # STATEMENT_URL未設定

    scraper.download_csv()

    assert driver.get.call_count == 1


@patch('scrapers.base_scraper.time.sleep')
def test_waits_after_each_download_click(mock_sleep):
    """ダウンロードクリック直後にdriver.quit()すると非同期ダウンロードが
    未完了のまま中断される実例があったため、各クリック後に待機すること"""
    driver = _make_driver()
    scraper = BaseScraper(MULTI_MONTH_CARD_CONFIG, driver_factory=lambda: driver,
                           today_func=lambda: date(2026, 7, 11))

    with patch('scrapers.base_scraper.Select'):
        scraper.download_csv()

    # デフォルト表示分 + 翌月分の、ダウンロードのたびに待機すること
    assert mock_sleep.call_count == 2


def test_download_wait_seconds_is_configurable():
    driver = _make_driver()
    config = dict(CARD_CONFIG, DOWNLOAD_WAIT_SECONDS=1)
    scraper = BaseScraper(config, driver_factory=lambda: driver)

    with patch('scrapers.base_scraper.time.sleep') as mock_sleep:
        scraper.download_csv()

    mock_sleep.assert_called_once_with(1)


def test_click_falls_back_to_javascript_when_intercepted():
    """バナー等に遮られて通常クリックが失敗した場合、JS経由のクリックにフォールバックすること"""
    download_button = _visible_element()
    download_button.click.side_effect = ElementClickInterceptedException('blocked by banner')

    def find_element_side_effect(by, xpath):
        if xpath == CARD_CONFIG['XPATH_DOWNLOAD_BUTTON']:
            return download_button
        return _visible_element()

    driver = _make_driver(find_element_side_effect=find_element_side_effect)
    scraper = BaseScraper(CARD_CONFIG, driver_factory=lambda: driver)

    scraper.download_csv()  # 例外が伝播しないこと

    download_button.click.assert_called_once()
    driver.execute_script.assert_any_call("arguments[0].click();", download_button)
