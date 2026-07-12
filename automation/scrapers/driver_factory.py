"""
本番実行(cron)向けChrome WebDriver生成

ホームサーバーはディスプレイの無いヘッドレス環境で、かつChromiumがsnap版で
導入されている（`google-chrome`という実行ファイル名ではない）ため、
`selenium.webdriver.Chrome()`をオプション無しでそのまま呼ぶだけでは動かない
（バイナリパスが自動検出できない、ヘッドレス指定が無いとディスプレイ不在で
起動に失敗する、ダウンロード先が `~/Downloads` 等になり `formatters/datas/`
に配置されないため後続のFormatter層が拾えない）。

ローカル(Mac)での目視デバッグ時（実サイト検証手順書7章）は、この関数を経由せず
呼び出し側でheadless=Falseの素朴なdriver_factoryを直接組み立てて使う想定。
"""

import os

from selenium import webdriver

DEFAULT_DOWNLOAD_DIR = os.path.join(
    os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'formatters', 'datas'
)


def build_driver_factory(scraper_config=None):
    """config.json の SCRAPER_CONFIG（任意）を元にdriver_factory関数を組み立てる

    Args:
        scraper_config (dict | None): config.json の SCRAPER_CONFIG 相当の辞書
            (CHROME_BINARY, DOWNLOAD_DIR, HEADLESS。全て任意)
            未設定時はヘッドレス・ダウンロード先 formatters/datas/ を既定値とする
            （cronでの無人実行を主眼としているため）。

    Returns:
        callable: 引数無しでSelenium WebDriverインスタンスを返す関数
    """
    scraper_config = scraper_config or {}
    chrome_binary = scraper_config.get('CHROME_BINARY')
    download_dir = scraper_config.get('DOWNLOAD_DIR', DEFAULT_DOWNLOAD_DIR)
    headless = scraper_config.get('HEADLESS', True)

    def driver_factory():
        options = webdriver.ChromeOptions()
        if chrome_binary:
            options.binary_location = chrome_binary
        if headless:
            options.add_argument('--headless=new')
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_experimental_option('excludeSwitches', ['enable-automation'])
        options.add_experimental_option('useAutomationExtension', False)
        options.add_argument('--disable-blink-features=AutomationControlled')
        prefs = {
            'download.default_directory': download_dir,
            'download.prompt_for_download': False,
            'download.directory_upgrade': True,
            'safebrowsing.enabled': False,
            'safebrowsing.disable_download_protection': True,
        }
        options.add_experimental_option('prefs', prefs)

        driver = webdriver.Chrome(options=options)
        driver.execute_cdp_cmd('Page.addScriptToEvaluateOnNewDocument', {
            'source': 'Object.defineProperty(navigator, "webdriver", {get: () => undefined})'
        })
        return driver

    return driver_factory
