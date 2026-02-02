#!/bin/sh
set -e

# Pastikan struktur storage & bootstrap/cache ada dan writable oleh www-data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

php artisan migrate --force

php artisan optimize:clear

php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

php artisan storage:link

# Semua file yang baru dibuat oleh artisan (root) harus dipindah ke www-data
# agar PHP-FPM (www-data) bisa tulis log, session, cache, dll.
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
