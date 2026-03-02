#!/bin/sh
set -e

# Imprimir comandos para facilitar depuración
# set -x

cd /var/www/html

echo "--- Iniciando Entrypoint ---"

# 1. Asegurar estructura de directorios crítica antes de cualquier comando Artisan
echo "Asegurando estructura de carpetas en storage y bootstrap/cache..."
mkdir -p storage/framework/sessions \
         storage/framework/views \
         storage/framework/cache \
         storage/framework/testing \
         storage/logs \
         bootstrap/cache

# 2. Corregir permisos de carpetas críticas al inicio
echo "Corrigiendo permisos iniciales..."
chmod -R 775 storage bootstrap/cache || true
# IMPORTANTE: php-fpm corre como www-data (uid 82 en Alpine).
# En bind mounts el owner es el del host (jeampier/root), no www-data.
# Sin chown, php-fpm no puede escribir vistas compiladas → 500 silencioso.
chown -R www-data:www-data storage bootstrap/cache || true

# 3. Instalar dependencias si no existen (volumen vacío en primer arranque)
if [ ! -d vendor ]; then
    echo "Carpeta vendor no encontrada. Ejecutando composer install..."
    composer install --no-dev --prefer-dist --no-interaction --no-progress
fi

# 4. Esperar a la base de datos
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-5432}

echo "Esperando a la base de datos en ${DB_HOST}:${DB_PORT}..."
while ! nc -z ${DB_HOST} ${DB_PORT}; do
  echo "Base de datos no disponible todavía. Reintentando en 2s..."
  sleep 2
done
echo "¡Base de datos conectada correctamente!"

# 5. Lógica según el tipo de instancia (APP vs WORKER)
# Identificamos el worker por los argumentos del comando original
if [ "$1" != "php" ] || [ "$2" != "artisan" ] || [ "$3" != "queue:work" ]; then
    echo "Instancia de APP detectada. Ejecutando mantenimiento..."
    
    # Limpiar caches previas para evitar inconsistencias
    php artisan optimize:clear || true
    
    # Cachear nueva configuración (tolerante a errores — no es crítico para el arranque)
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    php artisan event:cache || true
    
    # Ejecutar migraciones — SI FALLA, el contenedor debe detenerse
    # (no silenciar con || true, necesitamos saber si hay un problema)
    echo "Ejecutando migraciones (migrate --force)..."
    if ! php artisan migrate --force; then
        echo "=========================================="
        echo "❌ ERROR CRÍTICO: Las migraciones fallaron"
        echo "   Revisa los logs con: docker compose logs app"
        echo "=========================================="
        exit 1
    fi
    echo "✅ Migraciones completadas exitosamente."
else
    echo "Instancia de WORKER detectada. Saltando mantenimiento de app..."
    # A veces el worker necesita su propio cache de config si no se comparte el volumen de bootstrap/cache
    # Pero si comparten volumen, el worker usará lo que generó la app.
fi

# 6. Ejecución del comando final
if [ $# -gt 0 ]; then
    echo "Ejecutando comando: $@"
    exec "$@"
else
    echo "Iniciando php-fpm (default)..."
    exec php-fpm
fi
