#!/bin/sh
# Railway: bind nginx to $PORT, run migrations before accepting traffic.
set -e

cd /app

PORT="${PORT:-8000}"
export PORT

echo "[entrypoint] Florynn starting (PORT=${PORT}, APP_ENV=${APP_ENV:-prod})"

# Sync APP_URL for PHP (php-fpm may not see shell exports; .env is loaded by Symfony)
if [ -z "${APP_URL:-}" ] && [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
    export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
fi
if [ -n "${APP_URL:-}" ] && [ -f .env ]; then
    if grep -q '^APP_URL=' .env; then
        sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env
    else
        echo "APP_URL=${APP_URL}" >> .env
    fi
    echo "[entrypoint] APP_URL=${APP_URL}"
fi

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
    echo "[entrypoint] Created .env from .env.example"
    if [ -n "${APP_URL:-}" ]; then
        sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env 2>/dev/null || echo "APP_URL=${APP_URL}" >> .env
    fi
fi

mkdir -p var/cache var/log public/uploads/images config/jwt
chown -R www-data:www-data var public/uploads config/jwt 2>/dev/null || true

run_console() {
    if [ -f vendor/autoload_runtime.php ]; then
        php bin/console "$@"
    else
        echo "[entrypoint] Skip console: vendor/autoload_runtime.php missing"
        return 1
    fi
}

# --- JWT + database schema BEFORE HTTP (login needs `user` table) ---
if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
    echo "[entrypoint] Generating JWT key pair..."
    mkdir -p config/jwt
    run_console lexik:jwt:generate-keypair --skip-if-exists 2>/dev/null \
        && chown -R www-data:www-data config/jwt 2>/dev/null || true
fi

if [ -n "${DATABASE_URL:-}" ]; then
    echo "[entrypoint] Running database migrations (before HTTP)..."
    if ! run_console doctrine:migrations:migrate --no-interaction --allow-no-migration; then
        echo "[entrypoint] ERROR: migrations failed — fix DATABASE_URL on Railway app service"
        exit 1
    fi
    echo "[entrypoint] Migrations complete"
else
    echo "[entrypoint] WARN: DATABASE_URL not set — login will fail until MySQL is linked"
fi

# --- Nginx on Railway PORT ---
NGINX_TEMPLATE="/etc/nginx/templates/florynn.conf.template"
NGINX_SITE="/etc/nginx/conf.d/default.conf"
if [ -f "$NGINX_TEMPLATE" ]; then
    sed "s/__PORT__/${PORT}/g" "$NGINX_TEMPLATE" > "$NGINX_SITE"
    echo "[entrypoint] nginx listen ${PORT}"
fi

echo "[entrypoint] Testing nginx configuration..."
nginx -t

echo "[entrypoint] Starting PHP-FPM..."
php-fpm -D

echo "[entrypoint] Starting nginx on port ${PORT}..."
exec nginx -g 'daemon off;'
