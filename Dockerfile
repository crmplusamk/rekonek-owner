# ============================================
# Stage 1: Build Laravel dependencies
# ============================================
FROM php:8.1-fpm-alpine AS build

ENV TZ=Asia/Jakarta \
    APP_ENV=production

# Install dependencies untuk build
RUN apk add --no-cache \
    git unzip icu-dev libzip-dev libpng-dev libxml2-dev \
    postgresql-dev sqlite oniguruma-dev bash && \
    docker-php-ext-install intl pdo_pgsql bcmath gd zip opcache

# Install Composer dari official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy composer files dan install dependencies
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy seluruh source code Laravel
COPY . .

RUN composer dump-autoload --optimize

# ============================================
# Stage 2: Runtime container (PHP-FPM + Nginx + Supervisord)
# ============================================
FROM php:8.1-fpm-alpine AS runtime

ENV TZ=Asia/Jakarta \
    APP_ENV=production

# Install Nginx, Supervisord, su-exec (untuk jalankan artisan sebagai www-data) + PHP ext
RUN apk add --no-cache \
    nginx supervisor su-exec icu-dev libzip-dev libpng-dev libxml2-dev \
    postgresql-dev sqlite && \
    docker-php-ext-install intl pdo_pgsql bcmath gd zip opcache && \
    rm -rf /var/cache/apk/*

WORKDIR /var/www

# Copy Laravel dari build stage
COPY --from=build /var/www /var/www

# Copy konfigurasi Nginx & PHP-FPM
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/php-fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Copy konfigurasi supervisord
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint: siapkan storage/bootstrap, jalankan migrate + optimize, lalu supervisord
COPY docker/start-container.sh /usr/local/bin/start-container.sh
RUN chmod +x /usr/local/bin/start-container.sh

# Buat struktur storage & bootstrap/cache, set owner www-data, permission 775
RUN mkdir -p /var/www/storage/framework/{sessions,views,cache/data} /var/www/storage/logs /var/www/storage/app/public /var/www/bootstrap/cache && \
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    chown -R www-data:www-data /var/www/public

EXPOSE 80

# Jalankan script yang buat dir, migrate, cache, chown, lalu supervisord
CMD ["/usr/local/bin/start-container.sh"]
