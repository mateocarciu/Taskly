#!/bin/bash
set -e

echo "Starting Taskly..."

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache

php artisan config:clear
php artisan config:cache
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

php artisan migrate --force

chown -R www-data:www-data storage bootstrap/cache

echo "Taskly is ready."

if [ $# -gt 0 ]; then
    exec "$@"
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
