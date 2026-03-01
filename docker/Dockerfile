FROM php:8.2-fpm-alpine
RUN apk add --no-cache git curl bash libpq-dev oniguruma-dev zip unzip libzip-dev icu-dev netcat-openbsd \
  && docker-php-ext-configure intl \
  && docker-php-ext-install pdo pdo_pgsql mbstring intl zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
CMD ["php-fpm"]
