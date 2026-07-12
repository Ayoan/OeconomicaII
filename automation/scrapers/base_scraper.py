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

ログインフローは「ID・パスワードを同一画面で入力する1段階方式」と
「ID入力→次へ→別画面でパスワード入力する2段階方式(例: 楽天IDログインを
使うe-navi)」の両方に対応する。card_config に XPATH_NEXT_BUTTON が
設定されていれば2段階方式として扱う。

明細ダウンロードは「ログイン直後のデフォルト表示分」に加えて、
「お支払い月」等のプルダウンで対象月を切り替えて複数回ダウンロードする
サイト(例: SMBCカード。デフォルト表示は前月利用分の確定明細のみで、
当月利用分のプレビュー明細を見るには翌月の支払い月を選択し直す必要がある)
にも対応する。card_config に XPATH_MONTH_DROPDOWN /
XPATH_MONTH_SEARCH_BUTTON / ADDITIONAL_MONTH_OFFSETS が設定されていれば、
デフォルト表示分のダウンロード後、指定した支払い月ぶんだけ
「プルダウン選択→照会→ダウンロード」を追加実行する。

デフォルト表示画面と月切替後の画面とでダウンロードボタンのXPATHが異なる
サイト（実際にSMBCカードで確認済み）にも対応するため、月切替後のダウンロードには
XPATH_DOWNLOAD_BUTTON_ADDITIONAL（省略時は XPATH_DOWNLOAD_BUTTON と同一）を使う。
"""

import time
from datetime import date

from selenium.common.exceptions import ElementClickInterceptedException
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.select import Select
from selenium.webdriver.support.ui import WebDriverWait

DEFAULT_WAIT_TIMEOUT_SECONDS = 10
DEFAULT_DOWNLOAD_WAIT_SECONDS = 5


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

    def __init__(self, card_config, driver_factory, today_func=date.today):
        """
        Args:
            card_config (dict): config.json の CREDIT_CARDS.<KEY> 相当の辞書
            driver_factory (callable): Selenium WebDriverインスタンスを返す関数。
                ダウンロード先ディレクトリ等のブラウザオプションは呼び出し側
                （driver_factory の実装）の責務とし、本クラスでは扱わない
            today_func (callable): 現在日付を返す関数（ADDITIONAL_MONTH_OFFSETS利用時の
                対象月算出に使用。テスト時に日付を固定するためのフック）
        """
        self.card_config = card_config
        self.driver_factory = driver_factory
        self.today_func = today_func

    def download_csv(self):
        """ログイン → (必要なら明細ページへ遷移) → 明細CSVダウンロード（複数月ぶん）までを実行する

        実際のファイルダウンロードはブラウザのダウンロード設定に委譲するため、
        本メソッドはクリック操作の成否のみを扱う。

        Raises:
            LoginFailedError, StructureChangedError
        """
        driver = self.driver_factory()
        try:
            driver.get(self.card_config['LOGIN_URL'])
            self._login(driver)

            menu_link_xpath = self.card_config.get('XPATH_STATEMENT_MENU_LINK')
            if menu_link_xpath:
                # SPA等、URL直接遷移だとセッション/ルーティング状態が
                # 引き継がれずログイン画面に戻される実例があったサイト(例: SMBC)向け。
                # メニューリンクをクリックして画面遷移する
                self._click(driver, menu_link_xpath)
            else:
                statement_url = self.card_config.get('STATEMENT_URL')
                if statement_url:
                    # ログイン後にトップページ等へ遷移するサイト(例: e-navi)向け。
                    # 認証済みセッション内での同一ドメインへの直接遷移のため、
                    # メニュー展開等のクリック操作より壊れにくい
                    driver.get(statement_url)

            self._download_all_statements(driver)
        finally:
            driver.quit()

    def _download_all_statements(self, driver):
        """デフォルト表示分、および ADDITIONAL_MONTH_OFFSETS で指定された分だけ
        追加でダウンロードする（未設定時はデフォルト表示分のみ）

        月切替後の画面はデフォルト表示画面とダウンロードボタンのXPATHが
        異なる場合があるため、XPATH_DOWNLOAD_BUTTON_ADDITIONAL
        （省略時は XPATH_DOWNLOAD_BUTTON と同一）を使う。

        各クリック後、ファイルダウンロードが非同期で完了するのを待つため
        DOWNLOAD_WAIT_SECONDS（既定5秒）だけ待機する。待たずに driver.quit() すると
        ダウンロードが未完了(.crdownload等)のまま中断される実例があったため。
        """
        self._click(driver, self.card_config['XPATH_DOWNLOAD_BUTTON'])
        self._wait_for_download()

        additional_offsets = self.card_config.get('ADDITIONAL_MONTH_OFFSETS', [])
        if not additional_offsets:
            return

        additional_download_xpath = self.card_config.get(
            'XPATH_DOWNLOAD_BUTTON_ADDITIONAL', self.card_config['XPATH_DOWNLOAD_BUTTON']
        )
        for offset in additional_offsets:
            self._select_statement_month(driver, offset)
            self._click(driver, additional_download_xpath)
            self._wait_for_download()

    def _wait_for_download(self):
        wait_seconds = self.card_config.get('DOWNLOAD_WAIT_SECONDS', DEFAULT_DOWNLOAD_WAIT_SECONDS)
        time.sleep(wait_seconds)

    def _select_statement_month(self, driver, offset_months):
        """「お支払い月」等のプルダウンで対象月を選択し、照会ボタンを押す

        Args:
            offset_months (int): デフォルト表示の支払い月から何ヶ月後を選択するか
        """
        dropdown = self._find(driver, self.card_config['XPATH_MONTH_DROPDOWN'])
        target_text = self._target_month_text(offset_months)
        try:
            Select(dropdown).select_by_visible_text(target_text)
        except Exception as exc:
            raise StructureChangedError(self.card_config['XPATH_MONTH_DROPDOWN'], cause=exc) from exc

        self._click(driver, self.card_config['XPATH_MONTH_SEARCH_BUTTON'])

    def _target_month_text(self, offset_months):
        """現在日付を基準に「YYYY年M月」形式の対象月文字列を算出する

        デフォルト表示の支払い月を「実行日の月」とみなし、そこから
        offset_months ヶ月後の支払い月を表す文字列を返す
        （例: 実行日2026-07-11、offset_months=1 → '2026年8月'）。
        """
        today = self.today_func()
        total_months = today.year * 12 + (today.month - 1) + offset_months
        year, month0 = divmod(total_months, 12)
        return f"{year}年{month0 + 1}月"

    def _login(self, driver):
        self._fill(driver, self.card_config['XPATH_USERNAME_INPUT'], self.card_config['USER_ID'])

        next_button_xpath = self.card_config.get('XPATH_NEXT_BUTTON')
        if next_button_xpath:
            # 2段階ログイン方式（ID入力 → 次へ → 別画面でパスワード入力）
            self._click(driver, next_button_xpath)

        self._fill(driver, self.card_config['XPATH_PASSWORD_INPUT'], self.card_config['PASSWORD'])

        # ID/パスワード入力直後に即座にログインボタンを押すと、機械的な操作として
        # サイト側の不正アクセス検知にひっかかる（と疑われる）実例があったため、
        # PRE_LOGIN_CLICK_DELAY_SECONDS が設定されていれば人間の操作感を模して待機する
        delay = self.card_config.get('PRE_LOGIN_CLICK_DELAY_SECONDS', 0)
        if delay:
            time.sleep(delay)

        self._click(driver, self.card_config['XPATH_LOGIN_BUTTON'])
        self._check_login_error(driver)

    def _check_login_error(self, driver):
        """ログインエラー表示の有無を確認する（XPATH_LOGIN_ERROR_INDICATOR省略時はスキップ）"""
        error_xpath = self.card_config.get('XPATH_LOGIN_ERROR_INDICATOR')
        if not error_xpath:
            return
        try:
            element = driver.find_element(By.XPATH, error_xpath)
        except Exception:
            return  # エラー表示要素が見つからない = ログイン成功とみなす
        raise LoginFailedError(element.text or 'ログインエラー表示を検知しました')

    def _fill(self, driver, xpath, value):
        """入力欄に値を入力する

        Akamai Bot Manager等、キー入力速度からボット判定を行うと疑われる
        サイト向けに、TYPING_DELAY_SECONDS が設定されていれば1文字ずつ
        人間の打鍵間隔を模して送信する（未設定時は従来通り一括送信）。
        """
        element = self._find(driver, xpath)
        element.clear()
        typing_delay = self.card_config.get('TYPING_DELAY_SECONDS', 0)
        if typing_delay:
            for char in value:
                element.send_keys(char)
                time.sleep(typing_delay)
        else:
            element.send_keys(value)

    def _click(self, driver, xpath):
        """要素をクリックする

        バナー広告等、要素の上に別要素が重なってクリックが遮られる
        (ElementClickInterceptedException) 実例があったため、通常クリックが
        失敗した場合は要素を中央までスクロールしてJS経由でクリックする
        フォールバックを行う。
        """
        element = self._find(driver, xpath)
        try:
            element.click()
        except ElementClickInterceptedException:
            driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", element)
            driver.execute_script("arguments[0].click();", element)

    def _find(self, driver, xpath):
        """要素が操作可能になるまで待機して取得する

        2段階ログインの「次へ」クリック後など、ページ遷移・再描画の直後は
        要素がDOM上に存在してもまだ非表示/無効状態で ElementNotInteractableException
        になることがあるため、単なる存在確認(presence)ではなく
        クリック可能(表示・有効)になるまで WAIT_TIMEOUT_SECONDS（既定10秒）待機する。
        """
        timeout = self.card_config.get('WAIT_TIMEOUT_SECONDS', DEFAULT_WAIT_TIMEOUT_SECONDS)
        try:
            return WebDriverWait(driver, timeout).until(
                EC.element_to_be_clickable((By.XPATH, xpath))
            )
        except Exception as exc:
            raise StructureChangedError(xpath, cause=exc) from exc
