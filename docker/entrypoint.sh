#!/bin/sh
set -e

# Imprimir comandos para facilitar depuración
# set -x

cd /var/www/html

echo "--- Iniciando Entrypoint ---"

# Instalar dependencias si no existen
if [ ! -d vendor ]; then
    echo "Carpeta vendor no encontrada. Ejecutando composer install..."
    composer install --no-dev --prefer-dist --no-interaction --no-progress
fi

# Esperar a la base de datos
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-5432}

echo "Esperando a la base de datos en ${DB_HOST}:${DB_PORT}..."
while ! nc -z ${DB_HOST} ${DB_PORT}; do
  echo "Base de datos no disponible todavía. Reintentando en 2s..."
  sleep 2
done
echo "¡Base de datos conectada correctamente!"

# Optimizaciones básicas (útil en producción)
echo "Limpiando caches de Laravel..."
php artisan optimize:clear || true

# Ejecutar lógica solo si no es el worker (o si se desea forzar)
# Usualmente el worker y la app comparten el volumen, así que solo uno debe migrar
if [ "$1" != "php" ] || [ "$2" != "artisan" ] || [ "$3" != "queue:work" ]; then
    echo "Instancia de APP detectada. Ejecutando migraciones y cacheo..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    php artisan event:cache || true
    
    echo "Ejecutando php artisan migrate --force..."
    php artisan migrate --force || true
else
    echo "Instancia de WORKER detectada. Saltando migraciones y caches redundantes..."
fi

# Corregir permisos de carpetas críticas
echo "Corrigiendo permisos de storage y bootstrap/cache..."
chmod -R 775 storage bootstrap/cache || true
# chown -R www-data:www-data storage bootstrap/cache || true # Solo si el contenedor corre como root

if [ $# -gt 0 ]; then
    echo "Ejecutando comando personalizado: $@"
    exec "$@"
else
    echo "Iniciando php-fpm..."
    exec php-fpm
fi
