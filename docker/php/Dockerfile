# -------------------------
# 1. PHP + 必要拡張
# -------------------------
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpq-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# -------------------------
# 2. アプリをコピー
# -------------------------
WORKDIR /app
COPY ./src .

# Laravel インストール
RUN composer install --no-dev --optimize-autoloader

RUN chmod -R 775 storage bootstrap/cache

# -------------------------
# 3. Webサーバとして PHP-FPM を直接公開
#    Koyeb は Entrypoint で FPM をそのまま扱える
# -------------------------
EXPOSE 8000
CMD ["php-fpm"]
