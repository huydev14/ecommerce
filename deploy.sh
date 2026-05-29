#!/bin/bash

set -e

echo ">>> Pulling latest code from Git..."
git pull origin main

echo ">>> Restarting Docker containers..."
docker compose up -d

echo ">>> Running database migrations..."
docker compose exec -T web php artisan migrate --force

echo ">>> Optimizing Laravel Cache..."
docker compose exec -T web php artisan optimize
docker compose exec -T web php artisan view:cache

echo ">>> Restarting Queue Workers gracefully..."

docker compose exec -T web php artisan queue:restart

if docker compose exec -T web php artisan list | grep -q 'horizon:terminate'; then
    echo ">>> Restarting Laravel Horizon..."
    docker compose exec -T web php artisan horizon:terminate
fi

echo "=== DEPLOYMENT SUCCESSFUL ==="