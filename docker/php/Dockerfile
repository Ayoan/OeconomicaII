ARG PHP_VERSION

FROM php:${PHP_VERSION}-fpm

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client \
    cron \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# PHP拡張機能のインストール
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# タイムゾーンの設定（日本時間）
ENV TZ=Asia/Tokyo
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリの設定
WORKDIR /var/www/html

# supervisord設定ファイルをコピー
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# cronジョブファイルをコピー
COPY laravel-cron /etc/cron.d/laravel-cron
RUN chmod 0644 /etc/cron.d/laravel-cron && crontab /etc/cron.d/laravel-cron

# ログディレクトリの作成
RUN mkdir -p /var/log/supervisor

# エントリーポイントスクリプトをコピー
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# www-dataユーザーにディレクトリの所有権を付与
RUN chown -R www-data:www-data /var/www

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]