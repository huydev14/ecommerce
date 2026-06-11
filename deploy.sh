#!/bin/bash
set -e

artisan() {
    cd /srv/web-host
    docker compose exec -T php84 sh -lc "cd /var/www/html/app84/ecommerce && php artisan $*"
}

echo ">>> Running migrations"
artisan migrate --force

echo ">>> Clearing cache"
artisan optimize
artisan view:cache

echo ">>> Restarting queue"
artisan queue:restart

echo ">>> Restarting Laravel Horizon (nếu có)..."
if artisan list | grep -q 'horizon:terminate' || true; then
    artisan horizon:terminate || true
fi

echo ">>> DEPLOYMENT SUCCESSFUL"