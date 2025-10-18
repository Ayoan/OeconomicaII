#!/bin/sh
set -e

# Laravelプロジェクトが存在するか確認
if [ -f "composer.json" ]; then
    echo "Laravel project detected. Starting setup..."
    
    # vendor ディレクトリが存在しない場合、composer install を実行
    if [ ! -d "vendor" ]; then
        echo "Installing Composer dependencies..."
        composer install --no-interaction --optimize-autoloader --no-dev
    fi
    
    # .env ファイルが存在しない場合、.env.example からコピー
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            echo "Creating .env file from .env.example..."
            cp .env.example .env
        fi
    fi
    
    # アプリケーションキーが設定されていない場合、生成
    if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
        echo "Generating application key..."
        php artisan key:generate --force
    fi
    
    # ストレージとキャッシュのディレクトリの権限設定
    if [ -d "storage" ]; then
        chmod -R 775 storage
        chown -R www-data:www-data storage
    fi
    
    if [ -d "bootstrap/cache" ]; then
        chmod -R 775 bootstrap/cache
        chown -R www-data:www-data bootstrap/cache
    fi
    
    # データベース接続を待機
    echo "Waiting for database connection..."
    until php artisan migrate:status 2>/dev/null; do
        echo "Database is unavailable - sleeping"
        sleep 3
    done
    
    # マイグレーションの実行
    echo "Running database migrations..."
    php artisan migrate --force
    
    # キャッシュのクリアと最適化
    echo "Optimizing Laravel..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    echo "Laravel setup completed!"
fi

# 元のコマンドを実行
exec "$@"