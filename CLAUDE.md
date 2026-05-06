# OeconomicaII — CLAUDE.md

AIエージェントがこのリポジトリで作業するための包括的なコンテキストガイド。
このファイルを最初に読むことで、アプリの全体像・DB設計・改修履歴を把握できる。

---

## 1. アプリ概要

**OeconomicaII** は個人用家計簿管理Webアプリケーション。
夫婦の家計データを記録・分析するために使っており、常用している本番システム。

- **URL**: http://localhost:8000（Docker起動後）
- **phpMyAdmin**: http://localhost:18080
- **コンテナ名**: `Oeconomica_app`（PHP）、`Oeconomica_nginx`、`Oeconomica_db`

---

## 2. 技術スタック

| 層 | 技術 |
|---|---|
| バックエンド | Laravel 12.x (PHP 8.2+) |
| フロントエンド | Blade Templates + Tailwind CSS + Chart.js |
| データベース | MySQL 8.0 |
| インフラ | Docker + Docker Compose + Nginx |
| プロセス管理 | Supervisor（PHP-FPM + cron 並行実行） |
| タイムゾーン | Asia/Tokyo |
| 認証 | セッションベース（ユーザー名 + パスワード） |

---

## 3. ディレクトリ構成

```
OeconomicaII/
├── CLAUDE.md                      ← このファイル
├── docker-compose.yml
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   ├── php.ini
│   │   ├── supervisord.conf        ← PHP-FPM + cron を管理
│   │   ├── laravel-cron            ← cron定義（毎日0時にschedule:run）
│   │   └── entrypoint.sh
│   ├── nginx/default.conf
│   └── mysql/
│       ├── Dockerfile
│       └── my.cnf
└── src/                           ← Laravelアプリ本体
    ├── app/
    │   ├── Http/Controllers/
    │   │   ├── HouseholdController.php   ← メインコントローラー（全機能）
    │   │   └── Auth/
    │   │       ├── UserController.php    ← ログイン・ログアウト
    │   │       ├── RegisterController.php ← 新規登録・デフォルトカテゴリ作成
    │   │       └── PasswordResetController.php
    │   ├── Models/
    │   │   ├── Oeconomica.php     ← 収支データ
    │   │   ├── Category.php       ← カテゴリ
    │   │   ├── Subscription.php   ← サブスクリプション
    │   │   ├── Budget.php         ← 予算
    │   │   └── User.php
    │   ├── Services/
    │   │   └── ExchangeRateService.php   ← USD/JPY為替レート取得（1時間キャッシュ）
    │   ├── Console/Commands/
    │   │   └── ExecuteSubscriptions.php  ← Artisanコマンド: subscriptions:execute
    │   └── Mail/
    │       └── PasswordResetMail.php
    ├── database/migrations/       ← 時系列で変更履歴が分かる
    ├── resources/views/
    │   ├── household/
    │   │   ├── input.blade.php    ← 収支入力・一覧画面
    │   │   ├── monthly.blade.php  ← 月次レポート
    │   │   ├── yearly.blade.php   ← 年次レポート
    │   │   ├── settings.blade.php ← カテゴリ・サブスク・予算設定
    │   │   └── import.blade.php   ← CSV インポート
    │   ├── auth/                  ← ログイン・登録・パスワードリセット
    │   ├── layouts/app.blade.php  ← 共通レイアウト
    │   └── emails/password-reset.blade.php
    └── routes/
        ├── web.php                ← 全ルート定義
        └── console.php            ← スケジュール定義（subscriptions:execute を毎日実行）
```

---

## 4. データベーススキーマ

### `users` テーブル
| カラム | 型 | 説明 |
|---|---|---|
| id | bigint PK | |
| username | string(50) | ログインID（ユーザー名） |
| email | string(100) unique | |
| password | string | bcrypt |
| remember_token | string | |
| timestamps | | |

### `oeconomicas` テーブル（収支データ）
| カラム | 型 | 説明 |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | |
| balance | string | `income` / `expense` |
| date | date | 収支日 |
| category | string | カテゴリ名（文字列参照、FKなし） |
| amount | integer | 金額（円） |
| memo | string nullable | メモ（サブスク自動登録時: `定期: 名前 ($XX.XX)`） |
| timestamps | | |

**インデックス**: `(user_id, date)`, `(user_id, balance)`, `(user_id, date, balance)`, `(user_id, category, date)`

### `categories` テーブル
| カラム | 型 | 説明 |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | |
| category | string | カテゴリ名 |
| type | enum | `income` / `expense` |
| color | string(7) | `#RRGGBB` 形式 |
| sort_order | integer | 並び順（後から追加されたカラム） |
| timestamps | | |

**注意**: `oeconomicas.category` はこのテーブルの `category` カラムを文字列で参照している（外部キー制約なし）。カテゴリ削除時は使用中の収支データがないかチェック必要。

### `subscriptions` テーブル
| カラム | 型 | 説明 |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | |
| subscription | string | サブスクリプション名 |
| category | string | 支出カテゴリ名 |
| amount | float | 金額（通貨単位で） |
| currency | string(3) | `JPY` / `USD`（後から追加） |
| day | integer | 毎月何日に実行するか（1〜31） |
| is_active | boolean | 有効/無効 |
| payday | date nullable | 最後に実行された日（重複実行防止） |
| timestamps | | |

### `budgets` テーブル
| カラム | 型 | 説明 |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | |
| category | string | 支出カテゴリ名 |
| amount | integer | 予算額（円） |
| period | enum | `monthly` / `yearly` |
| timestamps | | |

**ユニーク制約**: `(user_id, category)` — 1カテゴリにつき1予算のみ

---

## 5. ルート一覧

```
GET  /                              → /login へリダイレクト（ゲスト）
GET  /login                         → ログインフォーム
POST /login                         → ログイン処理
GET  /register                      → 登録フォーム
POST /register                      → ユーザー登録
POST /logout                        → ログアウト（要認証）
GET  /password/reset                → パスワードリセット申請
POST /password/email                → リセットメール送信
GET  /password/reset/{token}        → リセットフォーム
POST /password/reset                → パスワード更新
POST /api/check-email               → メール重複チェック（Ajax）

[以下すべて /household/ プレフィックス、要認証]
GET  /input                         → 収支入力・一覧（?year=&month=）
POST /input                         → 収支登録
GET  /edit/{id}                     → 収支データ取得（Ajax JSON）
PUT  /update/{id}                   → 収支更新（Ajax or Form）
DELETE /delete/{id}                 → 収支削除（Ajax or Form）
GET  /monthly                       → 月次レポート（?month=YYYY-MM）
GET  /yearly                        → 年次レポート（?year=YYYY）
GET  /export-csv                    → CSVエクスポート（?year=&month=）
GET  /import                        → CSVインポートフォーム
POST /import                        → CSVインポート処理
GET  /settings                      → 設定画面（カテゴリ・サブスク・予算）
POST /category/store                → カテゴリ追加
PUT  /category/update/{id}          → カテゴリ更新
DELETE /category/delete/{id}        → カテゴリ削除
POST /category/reorder              → カテゴリ並び替え
POST /category/reset                → デフォルトカテゴリにリセット
POST /subscriptions/store           → サブスク追加
PUT  /subscriptions/update/{id}     → サブスク更新
DELETE /subscriptions/delete/{id}   → サブスク削除
POST /subscriptions/toggle/{id}     → サブスク有効/無効切替
POST /budgets/store                 → 予算設定（updateOrCreate）
PUT  /budgets/update/{id}           → 予算更新
DELETE /budgets/delete/{id}         → 予算削除
```

---

## 6. 主要な実装パターン

### Ajax vs フォーム送信
多くのエンドポイントは `$request->expectsJson()` で分岐し、
Ajax（JSON）とフォーム送信（リダイレクト）の両方に対応している。

### カテゴリ参照
`oeconomicas` テーブルの `category` カラムはカテゴリ名文字列を直接保存（FK なし）。
カテゴリ名変更時は既存収支データとの整合性に注意。

### サブスクリプション自動実行
- **スケジュール**: `console.php` → 毎日 Asia/Tokyo で `subscriptions:execute` を実行
- **cron**: `docker/php/laravel-cron` で毎日0時に `php artisan schedule:run`
- **重複防止**: `payday` カラムが今日の日付より前（またはnull）のものだけ実行
- **USD換算**: `ExchangeRateService` → exchangerate-api.com から取得、1時間キャッシュ、失敗時は150円フォールバック

### 為替レートキャッシュ
- Laravelのキャッシュ（Cache facade）を使用
- キャッシュキー: `exchange_rate_usd_jpy`
- TTL: 3600秒（1時間）

---

## 7. デフォルトカテゴリ

新規ユーザー登録時に `RegisterController::createDefaultCategories()` で自動作成される。

**収入（3種類）**: 給与、投資収入、その他

**支出（16種類）**: 食費、酒代、日用品、交通費、交際費、美容費、衣服費、医療費、書籍、サブスク、家賃、水道光熱費、通信費、家具・家電、旅行、その他

---

## 8. 改修履歴（Git ログ）

| コミット | 内容 |
|---|---|
| `87ec00d` | cronでphpコマンドが見つからない問題を修正 |
| `4bca4d7` | Supervisor/cronによるスケジュールタスク自動実行環境を構築 |
| `304a715` | サブスクリプション管理に通貨対応機能を追加（USD/JPY、為替レート自動取得） |
| `c8eba3e` | 予算管理機能を追加（月単位/年単位、カテゴリ別予算vs実績） |
| `0517e81` | サブスクリプション機能を追加（定期支出の自動登録） |
| `9406cf0` | ログイン方式をメールアドレスからユーザー名に変更 |
| `Add Category Setting Functions` | カテゴリ管理機能（色設定・並び替え・リセット）を追加 |
| `1dc370f` | 年次レポートUI修正 |
| `cb17271` | 月次レポート画面改善 |
| `70b33d4` | 月次レポート追加（カテゴリ別円グラフ・日別推移） |
| `983b3d1` | CSV Import/Export 実装 |
| `a541760` | ベース機能実装（収支入力・一覧・認証） |

---

## 9. 開発コマンド集

```bash
# コンテナ操作
docker-compose up -d                        # 起動
docker-compose down                         # 停止
docker-compose down -v                      # 停止（DBボリューム削除）

# Laravelコマンド（コンテナ内）
docker exec Oeconomica_app php artisan migrate              # マイグレーション実行
docker exec Oeconomica_app php artisan migrate:rollback     # 直前のマイグレーションを戻す
docker exec Oeconomica_app php artisan migrate:status       # マイグレーション状態確認
docker exec Oeconomica_app php artisan make:migration <name> --table=<table>  # マイグレーション作成
docker exec Oeconomica_app php artisan make:model <Name>    # モデル作成
docker exec Oeconomica_app php artisan make:controller <Name>  # コントローラー作成
docker exec Oeconomica_app php artisan view:clear           # ビューキャッシュクリア
docker exec Oeconomica_app php artisan config:clear         # 設定キャッシュクリア
docker exec Oeconomica_app php artisan cache:clear          # アプリキャッシュクリア（為替レートなど）

# サブスクリプション
docker exec Oeconomica_app php artisan subscriptions:execute  # 手動実行

# フロントエンド
docker exec Oeconomica_app npm run build    # Tailwind CSS / JS ビルド
docker exec Oeconomica_app npm run dev      # 開発モード（ホットリロード）

# ログ確認
docker exec Oeconomica_app tail -f storage/logs/laravel.log

# Supervisor（cron + PHP-FPM）の状態確認
docker exec Oeconomica_app supervisorctl status

# 為替レート確認
docker exec Oeconomica_app php artisan tinker --execute="\$service = app(\App\Services\ExchangeRateService::class); echo '1 USD = ' . \$service->getUsdToJpyRate() . ' JPY';"
```

---

## 10. 改修時の注意事項

### DBスキーマ変更時
1. `php artisan make:migration` でマイグレーションファイルを作成
2. `php artisan migrate` で適用
3. 対応するModelの `$fillable` や `$casts` を更新する

### 新機能追加の標準パターン
1. **マイグレーション** → テーブル/カラム追加
2. **Model** → `app/Models/` にモデル作成、リレーション・スコープ定義
3. **Controller** → `HouseholdController.php` にメソッド追加（または新コントローラー作成）
4. **Routes** → `routes/web.php` の `household` グループにルート追加
5. **View** → `resources/views/household/` にBladeテンプレート追加/編集
6. **フロント** → `npm run build` でアセット再ビルド

### フロントエンド変更時
Tailwind CSS のクラスを追加/変更した場合は必ず `npm run build` を実行する。
Chart.js はCDNから読み込んでいる（`layouts/app.blade.php` 参照）。

### カテゴリ名の取り扱い
`oeconomicas.category` はカテゴリ名を文字列で保存しているため、
カテゴリ名を変更する機能を追加する場合は既存収支データの `category` カラムも一括更新が必要。

### サブスクリプション通貨
USDサブスクリプションは登録時に原本のドル金額を保存し、実行時に為替換算して円で `oeconomicas` に保存する。
メモ欄に元の金額（例: `定期: Claude Pro ($20.00)`）が記録される。

### セキュリティ
- 全データはログインユーザーの `user_id` でフィルタリング
- 他ユーザーのデータへのアクセスは `where('user_id', $user->id)` で防止
- パスワードリセットはレート制限あり（`DebugRateLimit` コマンドで確認可能）

---

## 11. 環境設定

`.env` の主要項目（ルートの `.env` → docker-compose が参照）:
```
NGINX_PORT=8000
DB_DATABASE=oeconomica
DB_USERNAME=oeconomica_user
DB_PASSWORD=...
DB_ROOT_PASSWORD=...
PHP_VERSION=8.2
MYSQL_VERSION=8.0
TZ=Asia/Tokyo
```

アプリ側の `.env`（`src/.env`）:
```
APP_ENV=local
APP_KEY=base64:...
DB_HOST=db      ← Dockerネットワーク内のサービス名
DB_PORT=3306
MAIL_MAILER=...  ← パスワードリセットメール用
```
