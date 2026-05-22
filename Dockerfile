# syntax=docker/dockerfile:1

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
ENV COMPOSER_ALLOW_SUPERUSER=1

# Front-end build (needs lock files + assets; vendor comes after full COPY)
COPY package.json package-lock.json webpack.config.js ./
COPY assets ./assets

# Application code (vendor/ excluded via .dockerignore)
COPY . .

RUN npm ci && npm run build

# Composer: scripts MUST run so Symfony Flex creates vendor/autoload_runtime.php
RUN if [ "$INSTALL_DEV_DEPS" = "1" ]; then \
      composer install --no-interaction --prefer-dist --optimize-autoloader; \
    else \
      composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader; \
    fi \
    && test -f vendor/autoload_runtime.php \
    || (echo "ERROR: vendor/autoload_runtime.php missing — check composer install scripts" && exit 1)

COPY docker/nginx-main.conf /etc/nginx/nginx.conf
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

RUN mkdir -p var/cache var/log public/uploads/images config/jwt \
    && chown -R www-data:www-data var public/uploads config/jwt

RUN if [ ! -f .env ] && [ -f .env.example ]; then cp .env.example .env; fi

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
