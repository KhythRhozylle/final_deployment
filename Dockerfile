# syntax=docker/dockerfile:1
# v4 — composer before npm (ux-turbo needs vendor/); log: "Florynn Docker build v4"

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

# 1) PHP deps first — npm needs vendor/symfony/ux-turbo/assets (file: dependency)
COPY composer.json composer.lock symfony.lock ./
COPY docker/composer-install.sh docker/autoload_runtime.php ./docker/
RUN cp docker/composer-install.sh /usr/local/bin/composer-install.sh
RUN sed -i 's/\r$//' /usr/local/bin/composer-install.sh docker/autoload_runtime.php \
    && chmod +x /usr/local/bin/composer-install.sh \
    && echo "=== Florynn Docker build v4: composer (vendor for npm) ===" \
    && INSTALL_DEV_DEPS="$INSTALL_DEV_DEPS" /usr/local/bin/composer-install.sh

# 2) Front-end build (requires vendor from step 1)
COPY package.json package-lock.json webpack.config.js ./
COPY assets ./assets
ENV NODE_OPTIONS="--max-old-space-size=2048"
RUN echo "=== Florynn Docker build v4: npm ===" \
    && npm ci \
    && npm run build

# 3) Application code (vendor/ stays from step 1; not overwritten by .dockerignore)
COPY . .
RUN echo "=== Florynn Docker build v4: refresh autoload for src/ ===" \
    && if [ "$INSTALL_DEV_DEPS" = "1" ]; then \
      composer dump-autoload --optimize; \
    else \
      composer dump-autoload --optimize --no-dev; \
    fi

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
