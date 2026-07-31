# syntax=docker/dockerfile:1

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-progress \
    --optimize-autoloader

FROM php:8.3-fpm-alpine AS runner
WORKDIR /var/www/html

RUN apk add --no-cache \
        libpq-dev \
        icu-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql intl opcache \
    && apk del icu-dev

RUN addgroup -g 1000 www && adduser -G www -g www -s /bin/sh -D www

COPY --chown=www:www . .
COPY --from=vendor --chown=www:www /app/vendor ./vendor

RUN php artisan package:discover --ansi || true

USER www
EXPOSE 9000
CMD ["php-fpm"]
