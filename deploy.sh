#!/bin/bash
set -e

echo "---- Installing npm dependencies ----"
npm install

echo "---- Compiling frontend assets ----"
npm run build

echo "---- Clearing node_modules ----"
rm -rf node_modules

echo "---- Running migrations ----"
php artisan migrate --force

echo "---- Clearing cache ----"
php artisan optimize
php artisan view:cache

echo "---- Restarting queue ----"
php artisan queue:restart

echo "---- Restarting Laravel Horizon ----"
if grep -q 'horizon:terminate' || true; then
    php artisan horizon:terminate || true
fi

echo "---- DEPLOYMENT SUCCESSFUL ----"
