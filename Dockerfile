# syntax=docker/dockerfile:1
# Symfony 7 + Railway — v5 two-phase Composer (deps before bin/console, scripts after COPY . .)

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
    COMPOSER_NO_INTERACTION=1

# --- 1) Composer vendor/ only (no auto-scripts: bin/console not copied yet) ---
COPY composer.json composer.lock symfony.lock ./
COPY docker/composer-deps.sh docker/composer-finish.sh docker/autoload_runtime.php ./docker/
RUN sed -i 's/\r$//' docker/composer-deps.sh docker/composer-finish.sh docker/autoload_runtime.php \
    && chmod +x docker/composer-deps.sh docker/composer-finish.sh \
    && echo "=== Florynn Docker v5: composer-deps ===" \
    && INSTALL_DEV_DEPS="$INSTALL_DEV_DEPS" ./docker/composer-deps.sh

# --- 2) Front-end (file:vendor/symfony/ux-turbo needs vendor/ from step 1) ---
COPY package.json package-lock.json webpack.config.js ./
COPY assets ./assets
ENV NODE_OPTIONS="--max-old-space-size=2048"
RUN echo "=== Florynn Docker v5: npm ===" \
    && npm ci \
    && npm run build

# --- 3) Application (bin/console, config/, src/, public/, …) ---
COPY . .
RUN echo "=== Florynn Docker v5: composer-finish ===" \
    && sed -i 's/\r$//' bin/console 2>/dev/null || true \
    && chmod +x bin/console \
    && INSTALL_DEV_DEPS="$INSTALL_DEV_DEPS" ./docker/composer-finish.sh

# --- 4) Web server + permissions ---
COPY docker/nginx-main.conf /etc/nginx/nginx.conf
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p var/cache var/log public/uploads/images config/jwt \
    && chown -R www-data:www-data var public/uploads config/jwt

RUN if [ ! -f .env ] && [ -f .env.example ]; then cp .env.example .env; fi

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
