FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip curl \
        libpq-dev libicu-dev libzip-dev libonig-dev libxml2-dev \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
        zlib1g-dev $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql intl zip gd bcmath pcntl opcache exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000
