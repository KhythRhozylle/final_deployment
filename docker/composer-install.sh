#!/bin/sh
set -eu

export COMPOSER_ALLOW_SUPERUSER=1
export COMPOSER_NO_INTERACTION=1
unset COMPOSER_DISABLE_PLUGINS 2>/dev/null || true

INSTALL_DEV_DEPS="${INSTALL_DEV_DEPS:-0}"

echo "=== Florynn composer-install (plugins enabled, INSTALL_DEV_DEPS=${INSTALL_DEV_DEPS}) ==="

if [ "$INSTALL_DEV_DEPS" = "1" ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
else
  composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
fi

if [ ! -f vendor/autoload_runtime.php ]; then
  echo "[composer-install] autoload_runtime.php missing; running dump-autoload..."
  if [ "$INSTALL_DEV_DEPS" = "1" ]; then
    composer dump-autoload --optimize
  else
    composer dump-autoload --optimize --no-dev
  fi
fi

if [ ! -f vendor/autoload_runtime.php ] && [ -f docker/autoload_runtime.php ]; then
  echo "[composer-install] Using committed docker/autoload_runtime.php fallback"
  cp docker/autoload_runtime.php vendor/autoload_runtime.php
fi

if [ ! -f vendor/autoload_runtime.php ]; then
  echo "ERROR: vendor/autoload_runtime.php missing after composer install."
  exit 1
fi

echo "[composer-install] OK: vendor/autoload_runtime.php present"
