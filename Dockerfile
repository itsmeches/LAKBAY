FROM node:24-bookworm-slim AS assets
WORKDIR /src
COPY package*.json ./
RUN npm ci --ignore-scripts
COPY . .
RUN npm run build

FROM php:8.4-fpm-bookworm AS app
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev libonig-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev unzip clamav clamav-freshclam && docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install pdo_pgsql intl zip mbstring gd bcmath && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
COPY deploy/php.ini /usr/local/etc/php/conf.d/lakbay.ini
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
COPY --from=assets /src/public/build ./public/build
RUN php artisan filament:assets && mkdir -p storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views && chown -R www-data:www-data storage bootstrap/cache
USER www-data
CMD ["php-fpm"]

FROM nginx:stable-alpine AS web
COPY --from=app /var/www/public /var/www/public
COPY deploy/nginx.conf /etc/nginx/conf.d/default.conf
