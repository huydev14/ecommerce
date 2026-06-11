#!/bin/bash

set -e



echo ">>> Running database migrations"
ecommerce php artisan migrate --force

echo ">>> Clearing cache"
ecommerce php artisan optimize
ecommerce php artisan view:cache

echo ">>> Restarting Queue..."

ecommerce php artisan queue:restart

if ecommerce php artisan list | grep -q 'horizon:terminate'; then
    echo ">>> Restarting Laravel Horizon..."
    ecommerce php artisan horizon:terminate
fi

echo ">>> DEPLOYMENT SUCCESSFUL"