#!/bin/bash
set -e

echo "Starting Taskly..."

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache

php artisan migrate --force

php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

chown -R www-data:www-data storage bootstrap/cache

echo "Taskly is ready."

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
