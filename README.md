# Oeconomica II

日々の収支を記録・分析できる個人用家計簿管理Webアプリケーション

## 概要

Oeconomica IIは、収入と支出を記録し、月次・年次でビジュアルに分析できる家計簿管理システムです。カテゴリ別の集計グラフや前年比較など、家計の見える化をサポートします。

## 主な機能

### 収支管理
- 収入・支出データの登録・編集・削除
- 日付、カテゴリ、金額、メモの記録
- 月次カレンダー形式での一覧表示

### レポート機能
- **月次レポート**
  - カテゴリ別収支の円グラフ
  - 日別推移グラフ
  - 前月比較
- **年次レポート**
  - 月別収支推移グラフ
  - カテゴリ別年間集計
  - 前年比較
  - 最高・最低月の特定

### カテゴリ管理
- 収入・支出カテゴリのカスタマイズ
- カテゴリごとの色設定
- 並び順の変更
- デフォルトカテゴリへのリセット

### サブスクリプション管理
- 定期的な支出（サブスクリプション）の登録・編集・削除
- 毎月指定日に自動的に支出データとして登録
- サブスクリプション名、カテゴリ、金額、実行日の設定
- 有効/無効の切り替え機能
- 日次スケジュールによる自動実行

### 予算管理
- カテゴリごとの予算設定（月単位/年単位）
- 月次・年次レポートでの予算vs実績の比較表示
- 達成率の自動計算
- 予算残額の表示
- 予算超過時の警告表示

### データ管理
- CSV形式でのエクスポート
- CSV形式でのインポート（複数行エラー対応）

### セキュリティ
- ユーザー認証（ユーザー名/パスワード）
- パスワードリセット機能
- ブルートフォース攻撃対策（レート制限）

## 技術スタック

- **バックエンド**: Laravel 12.x (PHP 8.2+)
- **フロントエンド**: Blade Templates, Tailwind CSS, Chart.js
- **データベース**: MySQL 8.0
- **インフラ**: Docker, Docker Compose, Nginx

## 必要要件

- Docker
- Docker Compose

## セットアップ

### 1. リポジトリのクローン

```bash
git clone https://github.com/Ayoan/OeconomicaII.git
cd OeconomicaII
```

### 2. 環境設定ファイルの準備

```bash
cd src
cp .env.example .env
```

必要に応じて`.env`ファイルを編集してください。

### 3. Dockerコンテナの起動

```bash
cd ..
docker-compose up -d
```

### 4. 依存関係のインストール

```bash
docker exec Oeconomica_app composer install
docker exec Oeconomica_app npm install
docker exec Oeconomica_app npm run build
```

### 5. アプリケーションキーの生成

```bash
docker exec Oeconomica_app php artisan key:generate
```

### 6. データベースマイグレーション

```bash
docker exec Oeconomica_app php artisan migrate
```

### 7. ストレージパーミッションの設定

```bash
docker exec Oeconomica_app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec Oeconomica_app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

## アクセス

セットアップ完了後、以下のURLでアクセスできます：

- **アプリケーション**: http://localhost:8000
- **phpMyAdmin**: http://localhost:18080

## 使い方

### 初回利用

1. トップページから「新規会員登録」をクリック
2. ユーザー名、メールアドレス、パスワードを入力して登録
3. 登録時にデフォルトカテゴリ（収入3種類、支出16種類）が自動作成されます

### 収支の記録

1. ログイン後、「入力」画面で収支データを登録
2. 収入/支出、日付、カテゴリ、金額、メモを入力
3. 登録したデータは月次一覧で確認・編集可能

### レポートの確認

- **月次レポート**: カテゴリ別集計や日別推移をグラフで確認
- **年次レポート**: 年間の収支推移や前年比較を確認

### カテゴリのカスタマイズ

1. 「設定」画面からカテゴリを管理
2. 新規カテゴリの追加、既存カテゴリの編集・削除が可能
3. ドラッグ&ドロップで並び順を変更可能

### サブスクリプションの管理

1. 「設定」画面の「サブスクリプション管理」セクションで設定
2. サブスクリプション名、カテゴリ、金額、実行日（毎月X日）を登録
3. 登録したサブスクリプションは毎月指定日に自動的に支出として記録されます
4. 有効/無効の切り替えで一時的に停止することも可能

### 予算の設定

1. 「設定」画面の「予算管理」セクションで設定
2. カテゴリごとに月単位または年単位の予算を設定
3. 月次・年次レポートで予算との比較が自動的に表示されます
4. 予算超過時は赤色で警告表示されます

## デフォルトカテゴリ

### 収入（3種類）
- 給与
- 投資収入
- その他

### 支出（16種類）
- 食費、酒代、日用品、交通費
- 交際費、美容費、衣服費、医療費
- 書籍、サブスク、家賃、水道光熱費
- 通信費、家具・家電、旅行、その他

## 開発

### ビューキャッシュのクリア

```bash
docker exec Oeconomica_app php artisan view:clear
```

### ログの確認

```bash
docker exec Oeconomica_app tail -f storage/logs/laravel.log
```

### サブスクリプションの手動実行

```bash
docker exec Oeconomica_app php artisan subscriptions:execute
```

サブスクリプションは通常、毎日自動実行されますが、開発・テスト時に手動で実行することも可能です。

## ライセンス

このプロジェクトはMITライセンスの下で公開されています。

## 作者

Ayoan
