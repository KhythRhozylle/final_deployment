#!/bin/sh
# Railway: bind nginx to $PORT first, then run optional Symfony boot tasks.
set -e

cd /app

PORT="${PORT:-8000}"
export PORT

echo "[entrypoint] Florynn starting (PORT=${PORT}, APP_ENV=${APP_ENV:-prod})"

# Railway public URL for emails / OAuth
if [ -z "${APP_URL:-}" ] && [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
    export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
    echo "[entrypoint] APP_URL=${APP_URL}"
fi

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
    echo "[entrypoint] Created .env from .env.example (Railway env vars override this)"
fi

# Ensure writable runtime dirs (www-data serves PHP)
mkdir -p var/cache var/log public/uploads/images config/jwt
chown -R www-data:www-data var public/uploads config/jwt 2>/dev/null || true

# --- Nginx: always listen on Railway PORT (not hard-coded 8000) ---
NGINX_TEMPLATE="/etc/nginx/templates/florynn.conf.template"
NGINX_SITE="/etc/nginx/conf.d/default.conf"
if [ -f "$NGINX_TEMPLATE" ]; then
    sed "s/__PORT__/${PORT}/g" "$NGINX_TEMPLATE" > "$NGINX_SITE"
    echo "[entrypoint] nginx site written for listen ${PORT}"
else
    echo "[entrypoint] WARN: missing ${NGINX_TEMPLATE}, using packaged default.conf"
fi

echo "[entrypoint] Testing nginx configuration..."
nginx -t

echo "[entrypoint] Starting PHP-FPM..."
php-fpm -D

echo "[entrypoint] Starting nginx (foreground) on port ${PORT}..."
nginx -g 'daemon off;' &
NGINX_PID=$!

# Wait until health endpoint responds (Railway healthcheck / proxy)
TRIES=0
until curl -sf "http://127.0.0.1:${PORT}/health.html" >/dev/null 2>&1; do
    TRIES=$((TRIES + 1))
    if [ "$TRIES" -ge 30 ]; then
        echo "[entrypoint] ERROR: nginx not responding on port ${PORT}"
        exit 1
    fi
    sleep 1
done
echo "[entrypoint] HTTP ready on port ${PORT}"

run_console() {
    if [ -f vendor/autoload_runtime.php ]; then
        php bin/console "$@"
    else
        echo "[entrypoint] Skip console: vendor/autoload_runtime.php missing"
        return 1
    fi
}

# --- Background Symfony boot (do not block HTTP) ---
(
    set +e
    if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
        echo "[entrypoint] Generating JWT key pair..."
        mkdir -p config/jwt
        run_console lexik:jwt:generate-keypair --skip-if-exists 2>/dev/null \
            && chown -R www-data:www-data config/jwt 2>/dev/null || true
    fi

    if [ -n "${DATABASE_URL:-}" ]; then
        echo "[entrypoint] Running database migrations..."
        run_console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>/dev/null || true
    fi

    if [ "${APP_ENV:-prod}" = "prod" ] && [ "${WARM_CACHE_ON_START:-0}" = "1" ]; then
        echo "[entrypoint] Warming prod cache..."
        run_console cache:clear --env=prod --no-warmup 2>/dev/null || true
        run_console cache:warmup --env=prod 2>/dev/null || true
        chown -R www-data:www-data var/cache var/log 2>/dev/null || true
    fi
    echo "[entrypoint] Background boot tasks finished"
) &

wait $NGINX_PID
