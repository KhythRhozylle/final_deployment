#!/bin/sh
# Stage 2 (Docker): after COPY . . — refresh autoload for src/ and run Symfony auto-scripts.
set -eu

export COMPOSER_ALLOW_SUPERUSER=1
export COMPOSER_NO_INTERACTION=1
unset COMPOSER_DISABLE_PLUGINS 2>/dev/null || true

INSTALL_DEV_DEPS="${INSTALL_DEV_DEPS:-0}"
RUNTIME_FALLBACK="${RUNTIME_FALLBACK:-/usr/local/share/florynn/autoload_runtime.php}"

if [ ! -f bin/console ]; then
  echo "ERROR: bin/console not found in $(pwd). COPY the full application before composer-finish."
  exit 1
fi

chmod +x bin/console 2>/dev/null || true

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

mkdir -p var/cache var/log public/bundles

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

if [ ! -f vendor/autoload_runtime.php ] && [ -f "$RUNTIME_FALLBACK" ]; then
  cp "$RUNTIME_FALLBACK" vendor/autoload_runtime.php
elif [ ! -f vendor/autoload_runtime.php ] && [ -f docker/autoload_runtime.php ]; then
  cp docker/autoload_runtime.php vendor/autoload_runtime.php
fi

if [ ! -f vendor/autoload_runtime.php ]; then
  echo "ERROR: vendor/autoload_runtime.php missing after composer-finish"
  exit 1
fi

echo "[composer-finish] OK: bin/console and autoload_runtime.php ready"
