# syntax=docker/dockerfile:1
# v2 — composer install WITH scripts (not dump-autoload-only); see docker/composer-install.sh

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

COPY docker/composer-install.sh /usr/local/bin/composer-install.sh
RUN sed -i 's/\r$//' /usr/local/bin/composer-install.sh \
    && chmod +x /usr/local/bin/composer-install.sh \
    && INSTALL_DEV_DEPS="$INSTALL_DEV_DEPS" /usr/local/bin/composer-install.sh

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
