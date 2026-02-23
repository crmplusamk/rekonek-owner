#!/bin/bash
set -e

export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

nvm use 20

composer install --no-dev --optimize-autoloader

npm install
npm run build

php artisan migrate --force

php artisan optimize:clear

php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

#Initialized once
#php artisan storage:link
