#!/bin/sh
# Local helper: full install when the whole project is already on disk.
set -eu
export COMPOSER_ALLOW_SUPERUSER=1
INSTALL_DEV_DEPS="${INSTALL_DEV_DEPS:-0}"
sh docker/composer-deps.sh
if [ -f bin/console ]; then
  sh docker/composer-finish.sh
fi
