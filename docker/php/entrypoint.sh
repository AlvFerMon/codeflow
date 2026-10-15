#!/bin/sh
# Arranque del contenedor "app": prepara Laravel y lanza PHP-FPM.
set -e
cd /var/www/html

# 1. Fichero .env de Laravel (solo la primera vez)
if [ ! -f .env ]; then
    echo "[codeflow] Creando backend/.env a partir de .env.example"
    cp .env.example .env
fi

# 2. Dependencias PHP (vendor vive en un volumen de Docker). Se reinstalan si cambia composer.lock
LOCK_HASH="$(md5sum composer.lock | cut -d' ' -f1)"
if [ ! -f vendor/autoload.php ] || [ "$(cat vendor/.composer-lock-hash 2>/dev/null)" != "$LOCK_HASH" ]; then
    echo "[codeflow] Instalando dependencias con Composer (la primera vez tarda unos minutos)..."
    composer install --no-interaction --prefer-dist --no-progress --optimize-autoloader
    echo "$LOCK_HASH" > vendor/.composer-lock-hash
fi

# 3. Claves (se generan una sola vez y quedan guardadas en backend/.env)
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi
if ! grep -q '^CODEFLOW_MASTER_KEY=base64:' .env; then
    echo "[codeflow] Generando la clave maestra de cifrado (CODEFLOW_MASTER_KEY). ¡Haz copia de backend/.env!"
    MASTER_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    if grep -q '^CODEFLOW_MASTER_KEY=' .env; then
        sed -i "s|^CODEFLOW_MASTER_KEY=.*|CODEFLOW_MASTER_KEY=${MASTER_KEY}|" .env
    else
        printf '\nCODEFLOW_MASTER_KEY=%s\n' "$MASTER_KEY" >> .env
    fi
fi

# 4. Carpetas de trabajo de Laravel (las cachés compiladas van al volumen /var/www/cache)
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache /var/www/cache/views

# 5. Base de datos: migraciones + datos de prueba (el seeder no duplica si ya existen)
php artisan optimize:clear >/dev/null
echo "[codeflow] Ejecutando migraciones..."
php artisan migrate --force
if [ "${CODEFLOW_SEED_DEMO:-true}" = "true" ]; then
    php artisan db:seed --force
else
    # Producción: solo el administrador inicial (SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD)
    php artisan db:seed --class=AdminSeeder --force
fi

# 6. Cachés de Laravel (configuración, rutas, eventos y vistas precompiladas)
if [ "${CODEFLOW_OPTIMIZE:-true}" = "true" ]; then
    echo "[codeflow] Generando cachés de Laravel (tras cambiar rutas o config: docker compose restart app)"
    php artisan optimize >/dev/null
fi

# 7. Permisos: PHP-FPM se ejecuta como www-data (después de migrar, que corre como root)
chown -R www-data:www-data storage bootstrap/cache /var/www/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache /var/www/cache 2>/dev/null || true

echo "[codeflow] Listo. Web en ${APP_URL:-http://localhost:8080}"
exec "$@"
