#
# Stages:
#   base  - PHP-FPM + extensions (shared by dev and prod)
#   dev   - base + Composer + Node, source is bind-mounted (compose.yml)
#   build - dev + source, installs prod deps and compiles front-end assets
#   prod  - base + built app only (no Composer/Node)       (compose.prod.yml)
#   nginx - nginx + compiled public/ directory              (compose.prod.yml)

ARG PHP_VERSION=8.4
ARG NODE_VERSION=24

FROM node:${NODE_VERSION}-bookworm-slim AS node

# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-bookworm AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_mysql intl bcmath zip gd opcache pcntl \
    && rm -rf /var/lib/apt/lists/*

ENV TZ=Asia/Ulaanbaatar
WORKDIR /var/www/html

# ---------------------------------------------------------------------------
FROM base AS dev

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libstdc++6 default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Match www-data to the host user so files created in the container
# (composer, artisan make:*, npm) are owned by you on the host.
RUN groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g "${GID}" www-data \
    && chown -R www-data:www-data /var/www

COPY docker/php/php.dev.ini "$PHP_INI_DIR/conf.d/zz-app.ini"

USER www-data
# The bind-mounted repo is owned by the host user; let git inside the container use it.
RUN git config --global --add safe.directory /var/www/html
EXPOSE 9000
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
FROM dev AS build

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY --chown=www-data:www-data package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund

COPY --chown=www-data:www-data . .

# Wayfinder (run by the Vite plugin) boots the app to read routes, so a
# throwaway .env is used during the build and removed afterwards.
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && cp .env.example .env \
    && php artisan key:generate --force --no-interaction \
    && npm run build \
    && rm -rf node_modules .env bootstrap/cache/*.php public/hot

# ---------------------------------------------------------------------------
FROM base AS prod

COPY docker/php/php.prod.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY docker/php/fpm.prod.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/php/entrypoint.prod.sh /usr/local/bin/entrypoint
RUN chmod 755 /usr/local/bin/entrypoint

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

USER www-data
EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
FROM nginx:stable-alpine AS nginx

COPY docker/nginx/prod.conf /etc/nginx/conf.d/default.conf
COPY --from=build /var/www/html/public /var/www/html/public
