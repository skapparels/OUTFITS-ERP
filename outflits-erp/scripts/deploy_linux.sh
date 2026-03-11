#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"
FRONTEND_DIR="$ROOT_DIR/frontend"
FRONTEND_WEB_ROOT="/var/www/outflits-frontend"

cd "$ROOT_DIR"
git pull --rebase

cd "$BACKEND_DIR"
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

cd "$FRONTEND_DIR"
npm ci
npm run build
sudo mkdir -p "$FRONTEND_WEB_ROOT"
sudo rsync -av --delete dist/ "$FRONTEND_WEB_ROOT/"

sudo supervisorctl restart outflits-worker:* || true
sudo systemctl restart outflits-scheduler || true

echo "Deployment completed successfully."
