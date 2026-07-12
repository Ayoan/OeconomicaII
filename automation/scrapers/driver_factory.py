"""
本番実行(cron)向けChrome WebDriver生成

ホームサーバーはディスプレイの無いヘッドレス環境のため、
`selenium.webdriver.Chrome()`をオプション無しでそのまま呼ぶだけでは動かない
（ヘッドレス指定が無いとディスプレイ不在で起動に失敗する、ダウンロード先が
`~/Downloads` 等になり `formatters/datas/` に配置されないため後続の
Formatter層が拾えない）。

**snap版Chromium/chromedriverは使用しないこと（2026-07-12判明の既知の非互換）:**
Ubuntu の `apt install chromium chromium-driver` はsnap版を導入するが、
snap の AppArmor confinement により chromedriver からの `LaunchProcess` が
`failed to execvp` で失敗し、セッションを作成できない。また `/usr/bin/chromedriver`
はsnap版chromiumを起動する固定シェルスクリプトのラッパーであり、
`CHROME_BINARY` で非snap版（Google Chrome公式deb等）を指定してもAppArmorに
阻まれ `no chrome binary at ...` エラーになる。
対策: Google Chrome公式debパッケージ（`https://dl.google.com/linux/direct/
google-chrome-stable_current_amd64.deb`）と、Chrome for Testing配布の
スタンドアロンchromedriverバイナリ（`https://googlechromelabs.github.io/
chrome-for-testing/`）をChromeのバージョンに合わせて別途取得し、
`CHROMEDRIVER_PATH` で明示的に指定すること。

ローカル(Mac)での目視デバッグ時（実サイト検証手順書7章）は、この関数を経由せず
呼び出し側でheadless=Falseの素朴なdriver_factoryを直接組み立てて使う想定。
"""

import os

from selenium import webdriver
from selenium.webdriver.chrome.service import Service

DEFAULT_DOWNLOAD_DIR = os.path.join(
    os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'formatters', 'datas'
)


def build_driver_factory(scraper_config=None):
    """config.json の SCRAPER_CONFIG（任意）を元にdriver_factory関数を組み立てる

    Args:
        scraper_config (dict | None): config.json の SCRAPER_CONFIG 相当の辞書
            (CHROME_BINARY, CHROMEDRIVER_PATH, DOWNLOAD_DIR, HEADLESS。全て任意)
            未設定時はヘッドレス・ダウンロード先 formatters/datas/ を既定値とする
            （cronでの無人実行を主眼としているため）。

    Returns:
        callable: 引数無しでSelenium WebDriverインスタンスを返す関数
    """
    scraper_config = scraper_config or {}
    chrome_binary = scraper_config.get('CHROME_BINARY')
    chromedriver_path = scraper_config.get('CHROMEDRIVER_PATH')
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
        # 既定の'normal'(全リソースのロード完了を待つ)だと、広告バナー等の
        # サードパーティ製リソースがなかなか読み込み完了せずdriver.get()が
        # 数分単位でハングする実例があった(e-navi、2026-07-12)。DOM構築完了
        # (interactive)時点で制御を返す'eager'に変更し、この種のハングを防ぐ
        options.page_load_strategy = 'eager'
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

        service = Service(executable_path=chromedriver_path) if chromedriver_path else None
        driver = webdriver.Chrome(options=options, service=service)
        driver.execute_cdp_cmd('Page.addScriptToEvaluateOnNewDocument', {
            'source': 'Object.defineProperty(navigator, "webdriver", {get: () => undefined})'
        })
        return driver

    return driver_factory
