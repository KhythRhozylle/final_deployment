#!/bin/sh
set -e

cd /app

PORT="${PORT:-8000}"
export PORT

# Railway: set public URL when APP_URL is not provided
if [ -z "${APP_URL:-}" ] && [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
    export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
    echo "[entrypoint] APP_URL=${APP_URL}"
fi

# Ensure Symfony has a .env file (image may ship .env.dist only)
if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
    echo "[entrypoint] Created .env from .env.example"
fi

run_console() {
    if [ -f vendor/autoload_runtime.php ]; then
        php bin/console "$@"
    else
        echo "[entrypoint] Skip console: vendor/autoload_runtime.php missing"
        return 1
    fi
}

# JWT keys for API login
if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
    echo "[entrypoint] Generating JWT key pair..."
    mkdir -p config/jwt
    if run_console lexik:jwt:generate-keypair --skip-if-exists 2>/dev/null; then
        echo "[entrypoint] JWT keys ready"
    else
        echo "[entrypoint] Warning: could not generate JWT keys (set JWT_PASSPHRASE in env)"
    fi
fi

# Database migrations when DATABASE_URL is set
if [ -n "${DATABASE_URL:-}" ]; then
    echo "[entrypoint] Running database migrations..."
    run_console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
fi

APP_ENV="${APP_ENV:-prod}"
if [ "$APP_ENV" = "prod" ]; then
    echo "[entrypoint] Warming prod cache..."
    run_console cache:clear --env=prod --no-warmup || true
    run_console cache:warmup --env=prod || true
fi

# Bind nginx to Railway PORT (skip sed when config is read-only mounted at default 8000)
NGINX_SITE="/etc/nginx/conf.d/default.conf"
if [ -f "$NGINX_SITE" ] && [ "$PORT" != "8000" ]; then
    if touch "$NGINX_SITE" 2>/dev/null; then
        sed -i "s/listen 8000/listen ${PORT}/g" "$NGINX_SITE" || true
    else
        echo "[entrypoint] nginx listen stays 8000 (read-only config mount)"
    fi
fi

echo "[entrypoint] Testing nginx configuration..."
nginx -t

echo "[entrypoint] Starting PHP-FPM..."
php-fpm -D

echo "[entrypoint] Starting nginx on port ${PORT}..."
exec nginx -g 'daemon off;'
