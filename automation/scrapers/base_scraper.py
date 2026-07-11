"""
スクレイパー共通基盤

e-navi / SMBC 等クレジットカードサイトのスクレイピングに共通する
「ログイン → 明細CSVダウンロードボタン押下」の流れを提供する。

例外は2種類に区別する（CLAUDE.md 3章の例外処理フローに対応）:
- LoginFailedError: ID/パスワード誤り等、ログインフォーム自体は見つかったが認証が拒否された場合
- StructureChangedError: 想定していたXPATHの要素が見つからない = サイトのHTML構造が
  変わった可能性がある場合。自動復旧はできないため、LINE通知でユーザーに
  config.json のXPATH修正を依頼する運用とする。

XPATHの実値（config.json の CREDIT_CARDS.*）は各サイトの実際のDOM構造に
依存するため、ここではプレースホルダを前提としたロジックのみを実装する。
実際の値はユーザーが対象サイトを確認して config.json に設定する。
"""


class ScraperError(Exception):
    """スクレイピング処理全般の基底例外"""


class LoginFailedError(ScraperError):
    """ID/パスワードが誤っている、あるいはサイト側がログインを拒否した場合"""


class StructureChangedError(ScraperError):
    """想定していたHTML要素(XPATH)が見つからない = サイト構造が変わった可能性がある場合"""

    def __init__(self, xpath, cause=None):
        self.xpath = xpath
        super().__init__(f"要素が見つかりません(XPATH変更の可能性): {xpath}")
        if cause is not None:
            self.__cause__ = cause


class BaseScraper:
    """Selenium WebDriverを用いたクレカ明細ダウンロードの共通フロー

    サブクラスは SOURCE_NAME を上書きするだけでよい（ログインフローは
    config.json の XPATH_USERNAME_INPUT / XPATH_PASSWORD_INPUT / XPATH_LOGIN_BUTTON /
    XPATH_DOWNLOAD_BUTTON に沿った汎用実装で対応する）。
    サイト固有の追加手順（2段階認証等）が必要な場合はサブクラスで
    _login() をオーバーライドする。
    """

    SOURCE_NAME = 'unknown'

    def __init__(self, card_config, driver_factory):
        """
        Args:
            card_config (dict): config.json の CREDIT_CARDS.<KEY> 相当の辞書
            driver_factory (callable): Selenium WebDriverインスタンスを返す関数。
                ダウンロード先ディレクトリ等のブラウザオプションは呼び出し側
                （driver_factory の実装）の責務とし、本クラスでは扱わない
        """
        self.card_config = card_config
        self.driver_factory = driver_factory

    def download_csv(self):
        """ログイン → 明細CSVダウンロードボタンのクリックまでを実行する

        実際のファイルダウンロードはブラウザのダウンロード設定に委譲するため、
        本メソッドはクリック操作の成否のみを扱う。

        Raises:
            LoginFailedError, StructureChangedError
        """
        driver = self.driver_factory()
        try:
            driver.get(self.card_config['LOGIN_URL'])
            self._login(driver)
            self._click(driver, self.card_config['XPATH_DOWNLOAD_BUTTON'])
        finally:
            driver.quit()

    def _login(self, driver):
        self._fill(driver, self.card_config['XPATH_USERNAME_INPUT'], self.card_config['USER_ID'])
        self._fill(driver, self.card_config['XPATH_PASSWORD_INPUT'], self.card_config['PASSWORD'])
        self._click(driver, self.card_config['XPATH_LOGIN_BUTTON'])
        self._check_login_error(driver)

    def _check_login_error(self, driver):
        """ログインエラー表示の有無を確認する（XPATH_LOGIN_ERROR_INDICATOR省略時はスキップ）"""
        error_xpath = self.card_config.get('XPATH_LOGIN_ERROR_INDICATOR')
        if not error_xpath:
            return
        try:
            element = driver.find_element('xpath', error_xpath)
        except Exception:
            return  # エラー表示要素が見つからない = ログイン成功とみなす
        raise LoginFailedError(element.text or 'ログインエラー表示を検知しました')

    def _fill(self, driver, xpath, value):
        element = self._find(driver, xpath)
        element.clear()
        element.send_keys(value)

    def _click(self, driver, xpath):
        element = self._find(driver, xpath)
        element.click()

    def _find(self, driver, xpath):
        try:
            return driver.find_element('xpath', xpath)
        except Exception as exc:
            raise StructureChangedError(xpath, cause=exc) from exc
