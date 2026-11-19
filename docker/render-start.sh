#!/bin/sh
set -eu

port="${PORT:-10000}"
case "$port" in
    ''|*[!0-9]*) echo "PORT must be numeric." >&2; exit 1 ;;
esac

if [ -z "${APP_KEY:-}" ]; then
    echo "Set APP_KEY to a Laravel encryption key before starting." >&2
    exit 1
fi

mkdir -p "$(dirname "$DB_DATABASE")" storage/framework/cache/data storage/framework/sessions storage/framework/views
touch "$DB_DATABASE"
chown www-data:www-data "$(dirname "$DB_DATABASE")" "$DB_DATABASE"
chown -R www-data:www-data storage bootstrap/cache

runuser -u www-data -- php artisan migrate --force --seed --no-interaction
runuser -u www-data -- php artisan config:cache
runuser -u www-data -- php artisan view:cache

printf 'Listen %s\n' "$port" > /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$port>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
