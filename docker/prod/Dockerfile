# ---- composer deps ---------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-progress \
    --ignore-platform-req=ext-gd --ignore-platform-req=ext-exif

# ---- frontend build --------------------------------------------------------
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY lang ./lang
RUN npm run build

# ---- runtime ---------------------------------------------------------------
FROM php:8.4-fpm-alpine
LABEL org.opencontainers.image.title="Bet-Sefer"

RUN apk add --no-cache \
        libpq postgresql-dev icu-dev libzip-dev oniguruma-dev \
        libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev zlib-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql intl zip gd bcmath pcntl exif \
    && docker-php-ext-enable opcache \
    && apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --from=vendor /app/vendor /var/www/html/vendor
COPY --from=frontend /app/public/build /var/www/html/public/build

COPY . /var/www/html
COPY docker/prod/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Regenerates the optimized autoloader and runs package discovery with the app
# in place; runtime caches are built at deploy time once the real .env exists.
RUN composer dump-autoload --optimize --no-dev

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
    storage/logs storage/app/public/covers bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache \
    && rm -rf /var/www/html/node_modules /var/www/html/public/hot /tmp/composer

USER www-data

EXPOSE 9000
