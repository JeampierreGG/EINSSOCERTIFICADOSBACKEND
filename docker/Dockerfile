FROM php:8.4-fpm-alpine

RUN apk add --no-cache git curl bash libpq-dev oniguruma-dev zip unzip libzip-dev icu-dev netcat-openbsd \
    libpng-dev libjpeg-turbo-dev freetype-dev libxml2-dev \
  && docker-php-ext-configure intl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install pdo pdo_pgsql mbstring intl zip gd bcmath pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

CMD ["php-fpm"]
