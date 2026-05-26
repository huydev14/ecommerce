#!/bin/bash

git pull origin main

docker compose up -d --build

docker compose exec web php artisan migrate --force

docker compose exec web php artisan queue:flush
docker compose exec web php artisan horizon:terminate

echo "=== UPDATED ==="
