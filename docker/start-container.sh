#!/bin/sh
set -e

# Pastikan struktur storage & bootstrap/cache ada
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

# Fix ownership & permission (penting saat redeploy / volume persist dari host)
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Jalankan artisan sebagai www-data agar file log/cache/session langsung milik www-data
# (PHP-FPM juga www-data, sehingga tidak ada permission denied setelah redeploy)
su-exec www-data php artisan migrate --force

su-exec www-data php artisan optimize:clear

# su-exec www-data php artisan config:cache
# su-exec www-data php artisan event:cache
# su-exec www-data php artisan route:cache
# su-exec www-data php artisan view:cache

su-exec www-data php artisan storage:link

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
