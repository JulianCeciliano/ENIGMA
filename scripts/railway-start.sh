#!/bin/sh

set -eu

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

php artisan migrate --force

if [ -z "${PASSPORT_PRIVATE_KEY:-}" ] && [ -z "${PASSPORT_PUBLIC_KEY:-}" ] && [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --force --no-interaction
fi

php artisan optimize

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
