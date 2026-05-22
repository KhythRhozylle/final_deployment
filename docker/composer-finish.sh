#!/bin/sh
# Stage 2 (Docker): after COPY . . — refresh autoload for src/ and run Symfony auto-scripts (cache:clear, assets:install).
set -eu

export COMPOSER_ALLOW_SUPERUSER=1
export COMPOSER_NO_INTERACTION=1
unset COMPOSER_DISABLE_PLUGINS 2>/dev/null || true

INSTALL_DEV_DEPS="${INSTALL_DEV_DEPS:-0}"

if [ ! -f bin/console ]; then
  echo "ERROR: bin/console not found in $(pwd). COPY the full application before composer-finish."
  exit 1
fi

if [ ! -x bin/console ]; then
  chmod +x bin/console
fi

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

mkdir -p var/cache var/log public/bundles

# Production image: warm cache during build with prod env
export APP_ENV=prod
export APP_DEBUG=0

echo "=== composer-finish: autoload + Symfony post-install scripts ==="

if [ "$INSTALL_DEV_DEPS" = "1" ]; then
  composer dump-autoload --optimize
  composer run-script --no-interaction post-install-cmd
else
  composer dump-autoload --optimize --no-dev
  composer run-script --no-interaction post-install-cmd
fi

if [ ! -f vendor/autoload_runtime.php ] && [ -f docker/autoload_runtime.php ]; then
  cp docker/autoload_runtime.php vendor/autoload_runtime.php
fi

if [ ! -f vendor/autoload_runtime.php ]; then
  echo "ERROR: vendor/autoload_runtime.php missing after composer-finish"
  exit 1
fi

echo "[composer-finish] OK: bin/console and autoload_runtime.php ready"
