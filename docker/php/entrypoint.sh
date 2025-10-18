#!/bin/bash
set -e

echo "=== Laravel Auto Setup Script ==="

# Laravelプロジェクトが存在するか確認
if [ -f "composer.json" ]; then
    echo "✓ Laravel project detected"
    
    # vendor ディレクトリが存在しない場合、composer install を実行
    if [ ! -d "vendor" ]; then
        echo "→ Installing Composer dependencies..."
        composer install --no-interaction --optimize-autoloader --no-dev
        echo "✓ Composer dependencies installed"
    else
        echo "✓ Vendor directory already exists"
    fi
    
    # .env ファイルが存在しない場合の処理
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            echo "→ Creating .env file from .env.example..."
            cp .env.example .env
            echo "✓ .env file created"
        else
            echo "⚠ Warning: No .env or .env.example found"
        fi
    else
        echo "✓ .env file exists"
    fi
    
    # アプリケーションキーが設定されていない場合、生成
    if [ -f ".env" ]; then
        if ! grep -q "APP_KEY=base64:" .env 2>/dev/null || [ -z "$(grep "APP_KEY=" .env | cut -d '=' -f2)" ]; then
            echo "→ Generating application key..."
            php artisan key:generate --force
            echo "✓ Application key generated"
        else
            echo "✓ Application key already set"
        fi
    fi
    
    # ストレージとキャッシュのディレクトリの権限設定
    echo "→ Setting directory permissions..."
    if [ -d "storage" ]; then
        chmod -R 775 storage
        chown -R www-data:www-data storage
    fi
    
    if [ -d "bootstrap/cache" ]; then
        chmod -R 775 bootstrap/cache
        chown -R www-data:www-data bootstrap/cache
    fi
    echo "✓ Permissions set"
    
    # データベース接続を待機
    echo "→ Waiting for database connection..."
    max_attempts=30
    attempt=0
    
    # 環境変数から取得（docker-compose.ymlで設定された値）
    DB_HOST="${DB_HOST:-db}"
    DB_PORT="${DB_PORT:-3306}"
    DB_DATABASE="${DB_DATABASE:-}"
    DB_USERNAME="${DB_USERNAME:-}"
    DB_PASSWORD="${DB_PASSWORD:-}"
    
    # 環境変数が設定されていない場合は.envから読み取る
    if [ -z "$DB_DATABASE" ] && [ -f ".env" ]; then
        DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2 | tr -d '\r' | tr -d ' ')
        DB_PORT=$(grep "^DB_PORT=" .env | cut -d '=' -f2 | tr -d '\r' | tr -d ' ')
        DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2 | tr -d '\r' | tr -d ' ')
        DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2 | tr -d '\r' | tr -d ' ')
        DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2 | tr -d '\r' | tr -d ' ')
    fi
    
    echo "  Using DB: ${DB_DATABASE}@${DB_HOST}:${DB_PORT} (user: ${DB_USERNAME})"
    
    # php artisan を使用してデータベース接続を確認
    until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null || [ $attempt -eq $max_attempts ]; do
        attempt=$((attempt+1))
        if [ $attempt -eq 1 ] || [ $((attempt % 5)) -eq 0 ]; then
            echo "  Database connection attempt $attempt/$max_attempts..."
        fi
        sleep 2
    done
    
    if [ $attempt -eq $max_attempts ]; then
        echo "⚠ Warning: Database connection timeout, skipping migrations"
        echo "  You may need to run 'php artisan migrate' manually"
        echo "  Debug: Trying connection one more time with error output..."
        php -r "try { new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); } catch (Exception \$e) { echo 'Error: ' . \$e->getMessage() . PHP_EOL; }" || true
    else
        echo "✓ Database connected (attempt $attempt)"
        
        # マイグレーションの実行
        echo "→ Running database migrations..."
        if php artisan migrate --force 2>&1; then
            echo "✓ Migrations completed successfully"
        else
            echo "⚠ Warning: Migration encountered issues"
            echo "  Check logs above for details"
        fi
        
        # キャッシュのクリアと最適化
        echo "→ Optimizing Laravel..."
        php artisan config:clear 2>/dev/null || true
        php artisan config:cache 2>/dev/null || true
        php artisan route:clear 2>/dev/null || true
        php artisan route:cache 2>/dev/null || true
        php artisan view:clear 2>/dev/null || true
        php artisan view:cache 2>/dev/null || true
        echo "✓ Optimization completed"
    fi
    
    echo "=== Laravel setup completed! ==="
else
    echo "⚠ No composer.json found, skipping Laravel setup"
fi

# 元のコマンドを実行
exec "$@"