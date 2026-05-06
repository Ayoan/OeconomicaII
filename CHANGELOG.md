# OeconomicaII 改修履歴

---

## [2026-05-06] サブスクリプション金額入力バグ修正

### 問題
サブスクリプション追加・編集モーダルで、金額に4桁以上の数値を入力するとフィールドがリセットされ入力できなくなっていた。

### 原因
`settings.blade.php` の `formatSubscriptionAmount` 関数が `parseInt(value).toLocaleString()` によりカンマ付き文字列（例: `1,000`）を生成し、`type="number"` の input 要素にセットしていた。`type="number"` はカンマを含む値を無効と判定して空文字にリセットするため、4桁以上の入力が破棄されていた。また、同関数が `replace(/[^\d]/g, '')` で小数点を除去するため、USD金額（`step="0.01"`）の入力も破壊されていた。

### 修正内容
**対象ファイル**: `src/resources/views/household/settings.blade.php`

- `formatSubscriptionAmount` 関数を削除
- `add-subscription-amount` および `edit-subscription-amount` への `input` イベントリスナー登録を削除

`type="number"` 入力はブラウザが数値バリデーションを行うため、追加のフォーマット処理は不要。送信時のカンマ除去処理（`formData.get('amount').replace(/,/g, '')`）はそのまま残置（副作用なし）。

### 関連コミット
- `87ec00d` cronでphpコマンドが見つからない問題を修正
- `4bca4d7` Supervisor/cronによるスケジュールタスク自動実行環境を構築
- `304a715` サブスクリプション管理に通貨対応機能を追加（本修正はこのコミット由来の不具合）

---

## [2026-05-06] 予算管理・通貨対応（参考：過去の主要改修）

Git ログベースの主要改修履歴は `CLAUDE.md` セクション8を参照。

