# Single-stage image for Render's free tier - a demo/testing deployment, not
# a high-traffic production setup, so `php artisan serve` behind Render's
# assigned $PORT is enough; no nginx/php-fpm split needed for this scale.
FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
        libzip-dev zip unzip git curl libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Render provides $PORT at runtime - it is not known at build time, so the
# app must bind to it in the start command, not bake a fixed port here.
CMD php artisan migrate --force \
    && php artisan storage:link \
    && php artisan config:cache \
    && php artisan serve --host 0.0.0.0 --port "${PORT:-10000}"
