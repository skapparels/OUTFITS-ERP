# Deploy Now (GoDaddy Web Hosting Starter)

If your GoDaddy hosting is ready, run this checklist exactly.

## 0) One-time local prerequisites

- Node 20+
- lftp installed

```bash
# Ubuntu/Debian
sudo apt update && sudo apt install -y lftp
```

## 1) Build frontend

```bash
cd outflits-erp/frontend
npm ci
npm run build
```

## 2) Upload release package now

```bash
cd ../
export GODADDY_HOST="ftp.yourdomain.com"
export GODADDY_USER="cpanel-ftp-user"
export GODADDY_PASS="your-ftp-password"
# Optional overrides
# export GODADDY_PROTOCOL="ftp"
# export GODADDY_PORT="21"
# export GODADDY_REMOTE_BASE="/"
# export GODADDY_API_DOCROOT="public_html/api"
# export GODADDY_APP_DOCROOT="public_html"

bash scripts/deploy_godaddy_now.sh
```

This uploads `dist/godaddy_starter_package.zip` to `outflits-erp-release/` on your hosting.

## 3) In cPanel File Manager (required)

1. Go to `outflits-erp-release/`
2. Extract `godaddy_starter_package.zip`
3. Keep final path like:

```text
/home/<cpanel_user>/outflits-erp-release/godaddy_starter/backend
/home/<cpanel_user>/outflits-erp-release/godaddy_starter/frontend-dist
```

## 4) Wire document roots

- API subdomain docroot -> backend public folder
  - `/home/<cpanel_user>/outflits-erp-release/godaddy_starter/backend/public`
- App domain docroot -> frontend-dist contents

If docroot cannot be changed on your plan, use fallback htaccess templates:
- `backend/deploy/godaddy/api-root-fallback.htaccess`
- `backend/deploy/godaddy/frontend.htaccess`

## 5) Configure backend

Inside backend directory:

```bash
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan jwt:secret
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set in `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=sync
```

## 6) Add cron in cPanel

```cron
* * * * * /usr/local/bin/php /home/<cpanel_user>/outflits-erp-release/godaddy_starter/backend/artisan schedule:run >> /home/<cpanel_user>/logs/outflits-scheduler.log 2>&1
```

## 7) Smoke test

- `https://api.yourdomain.com/api/v1/auth/login`
- frontend loads from your app domain
- POS offline queue sync endpoint works: `POST /api/v1/sales/offline-sync`
