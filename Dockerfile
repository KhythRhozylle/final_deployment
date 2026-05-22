# syntax=docker/dockerfile:1
# Symfony 7 + Railway — v6 (build scripts in /usr/local/bin, not overwritten by COPY . .)

FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    curl \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl opcache zip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ARG INSTALL_DEV_DEPS=0
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    RUNTIME_FALLBACK=/usr/local/share/florynn/autoload_runtime.php

# Install build scripts outside /app so COPY . . cannot strip +x (Windows → Linux)
COPY docker/composer-deps.sh /usr/local/bin/florynn-composer-deps
COPY docker/composer-finish.sh /usr/local/bin/florynn-composer-finish
COPY docker/autoload_runtime.php /usr/local/share/florynn/autoload_runtime.php
RUN sed -i 's/\r$//' /usr/local/bin/florynn-composer-deps /usr/local/bin/florynn-composer-finish \
    /usr/local/share/florynn/autoload_runtime.php \
    && chmod 755 /usr/local/bin/florynn-composer-deps /usr/local/bin/florynn-composer-finish

# --- 1) Composer vendor/ (no Symfony scripts yet — bin/console not in image) ---
COPY composer.json composer.lock symfony.lock ./
RUN echo "=== Florynn Docker v6: composer-deps ===" \
    && INSTALL_DEV_DEPS="$INSTALL_DEV_DEPS" sh /usr/local/bin/florynn-composer-deps

# --- 2) Front-end (needs vendor/symfony/ux-turbo from step 1) ---
COPY package.json package-lock.json webpack.config.js ./
COPY assets ./assets
ENV NODE_OPTIONS="--max-old-space-size=2048"
RUN echo "=== Florynn Docker v6: npm ===" \
    && npm ci \
    && npm run build

# --- 3) Application + Symfony post-install (cache:clear, assets:install) ---
COPY . .
RUN echo "=== Florynn Docker v6: composer-finish ===" \
    && sed -i 's/\r$//' bin/console \
    && chmod 755 bin/console \
    && INSTALL_DEV_DEPS="$INSTALL_DEV_DEPS" sh /usr/local/bin/florynn-composer-finish

# --- 4) Web server + permissions ---
COPY docker/nginx-main.conf /etc/nginx/nginx.conf
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod 755 /usr/local/bin/entrypoint.sh \
    && mkdir -p var/cache var/log public/uploads/images config/jwt \
    && chown -R www-data:www-data var public/uploads config/jwt

RUN if [ ! -f .env ] && [ -f .env.example ]; then cp .env.example .env; fi

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
