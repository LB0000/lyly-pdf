FROM php:8.3-fpm

# システム依存パッケージ + Caddy + supervisord
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    libsqlite3-dev \
    supervisor \
    curl \
    && curl -fsSL https://caddyserver.com/api/download?os=linux&arch=amd64 -o /usr/local/bin/caddy \
    && chmod +x /usr/local/bin/caddy \
    && rm -rf /var/lib/apt/lists/*

# PHP拡張モジュール
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd curl mbstring zip fileinfo pdo_sqlite

# PHP設定
RUN echo "memory_limit = 1024M" > /usr/local/etc/php/conf.d/lyly.ini \
    && echo "max_execution_time = 600" >> /usr/local/etc/php/conf.d/lyly.ini \
    && echo "upload_max_filesize = 20M" >> /usr/local/etc/php/conf.d/lyly.ini \
    && echo "post_max_size = 20M" >> /usr/local/etc/php/conf.d/lyly.ini \
    && echo "date.timezone = Asia/Tokyo" >> /usr/local/etc/php/conf.d/lyly.ini

# php-fpm: localhostでリッスン（Caddyと接続）
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 127.0.0.1:9000/' /usr/local/etc/php-fpm.d/www.conf || true

WORKDIR /app

# アプリケーションコピー
COPY config.php api.php run.php index.php ./
COPY include/ ./include/
COPY parts/ ./parts/
COPY guidelines/ ./guidelines/

# Caddy設定 + supervisord設定
COPY Caddyfile /etc/caddy/Caddyfile
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 書き込み用ディレクトリ作成
RUN mkdir -p download temp draft output uploads \
    && chmod -R 777 download temp draft output uploads

EXPOSE 8080

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
