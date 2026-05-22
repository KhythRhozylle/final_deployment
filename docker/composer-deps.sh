#!/bin/sh
# Stage 1 (Docker): install vendor/ only. No post-install scripts — bin/console is not in the image yet.
set -eu

export COMPOSER_ALLOW_SUPERUSER=1
export COMPOSER_NO_INTERACTION=1
unset COMPOSER_DISABLE_PLUGINS 2>/dev/null || true

INSTALL_DEV_DEPS="${INSTALL_DEV_DEPS:-0}"
RUNTIME_FALLBACK="${RUNTIME_FALLBACK:-/usr/local/share/florynn/autoload_runtime.php}"

echo "=== composer-deps: vendor install (--no-scripts) ==="

if [ "$INSTALL_DEV_DEPS" = "1" ]; then
  composer install --no-interaction --prefer-dist --no-scripts
else
  composer install --no-interaction --prefer-dist --no-dev --no-scripts
fi

if [ ! -f vendor/autoload_runtime.php ]; then
  echo "[composer-deps] Generating autoload (Symfony Runtime plugin)..."
  if [ "$INSTALL_DEV_DEPS" = "1" ]; then
    composer dump-autoload --optimize
  else
    composer dump-autoload --optimize --no-dev
  fi
fi

if [ ! -f vendor/autoload_runtime.php ] && [ -f "$RUNTIME_FALLBACK" ]; then
  echo "[composer-deps] Fallback: $RUNTIME_FALLBACK"
  cp "$RUNTIME_FALLBACK" vendor/autoload_runtime.php
elif [ ! -f vendor/autoload_runtime.php ] && [ -f docker/autoload_runtime.php ]; then
  cp docker/autoload_runtime.php vendor/autoload_runtime.php
fi

if [ ! -f vendor/autoload_runtime.php ]; then
  echo "ERROR: vendor/autoload_runtime.php missing after composer-deps"
  exit 1
fi

echo "[composer-deps] OK"
