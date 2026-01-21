#!/bin/sh
set -e
cd /var/www/html
if [ ! -d vendor ]; then
  composer install --no-dev --prefer-dist --no-interaction --no-progress
fi

DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-5432}
echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
for i in $(seq 1 60); do
  nc -z ${DB_HOST} ${DB_PORT} && break
  sleep 2
done

php artisan optimize:clear || true
php artisan config:cache || true
php artisan migrate --force || true
if [ $# -gt 0 ]; then
    exec "$@"
else
    exec php-fpm
fi
