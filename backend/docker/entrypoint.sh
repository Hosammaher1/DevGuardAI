#!/bin/sh
set -e

# Default PORT to 10000 if not supplied by Render
export PORT="${PORT:-10000}"

# Substitute PORT in Nginx configuration template
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Production Database Guard: Prevent SQLite usage in production
if [ "$APP_ENV" = "production" ] && [ "$DB_CONNECTION" = "sqlite" ]; then
    echo "FATAL: SQLite is strictly disallowed in production environment." >&2
    echo "Please set DB_CONNECTION=pgsql and provide PostgreSQL connection variables." >&2
    exit 1
fi

# Ensure storage directories exist
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/app/private \
         /var/www/html/storage/app/public \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# If in production and APP_KEY is set, optimize Laravel runtime caches
if [ "$APP_ENV" = "production" ] && [ -n "$APP_KEY" ]; then
    echo "Optimizing configuration, routes, and views for production..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Start PHP-FPM in background
echo "Starting PHP-FPM daemon..."
php-fpm -D

# Start Nginx in foreground listening on 0.0.0.0:$PORT
echo "DevGuard API listening on 0.0.0.0:${PORT}..."
exec nginx -g "daemon off;"
