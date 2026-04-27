#!/bin/sh
set -e

cd /var/www/html

if [ "$1" = "php-fpm" ]; then
    if [ -z "$APP_KEY" ]; then
        echo "ERROR: APP_KEY is not set. Run 'make prod-key' and put the value in .env.production." >&2
        exit 1
    fi

    # Environment is only known at runtime, so caches are built on start.
    php artisan optimize --no-interaction
fi

exec "$@"
