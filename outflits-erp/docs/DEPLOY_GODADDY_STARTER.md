# GoDaddy Web Hosting Starter Deployment (cPanel / Shared Hosting)

This guide is optimized for **GoDaddy Web Hosting Starter** where you usually have:
- cPanel + File Manager
- MySQL database access
- PHP selector
- Cron jobs
- Limited/no root access and often no Supervisor/systemd

## 1) What to deploy

Deploy two apps:
1. **Backend API (Laravel)** on subdomain, e.g. `api.yourdomain.com`
2. **Frontend static build (Vite React)** on main domain or subdomain, e.g. `app.yourdomain.com`

## 2) cPanel prerequisites

- Create MySQL DB + DB user in cPanel
- Set PHP version to **8.2+**
- Enable extensions: `mbstring`, `pdo_mysql`, `bcmath`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`
- Create subdomains for `api` and `app`

## 3) Build artifacts locally

From your local/dev machine:

```bash
cd outflits-erp/frontend
npm ci
npm run build
```

Zip backend + frontend build payload:

```bash
cd outflits-erp
bash scripts/package_godaddy_starter.sh
```

This creates `dist/godaddy_starter_package.zip`.

## 4) Upload files in cPanel

- Upload `godaddy_starter_package.zip` to home directory and extract.
- Suggested layout after extraction:

```text
/home/<cpanel_user>/outflits-erp/
  backend/
  frontend-dist/
```

## 5) Configure API document root

Preferred: set API subdomain document root to:

```text
/home/<cpanel_user>/outflits-erp/backend/public
```

If GoDaddy plan prevents changing document root cleanly, use the fallback `.htaccess` templates under `backend/deploy/godaddy/`.

## 6) Backend environment

Inside backend:

- Copy `.env.example` to `.env`
- Fill DB credentials and app URL
- Set queue on shared hosting to sync:

```dotenv
QUEUE_CONNECTION=sync
APP_ENV=production
APP_DEBUG=false
```

Run (via SSH Terminal if available):

```bash
cd ~/outflits-erp/backend
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan jwt:secret
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If SSH is not available, run these with cPanel Terminal (if enabled) or ask GoDaddy support to enable SSH.

## 7) Frontend deployment

Upload `frontend-dist/` contents to app domain web root (`public_html` or app subdomain root).

For SPA routing, copy `backend/deploy/godaddy/frontend.htaccess` into frontend web root as `.htaccess`.

## 8) Scheduler with cPanel Cron

Add cron job in cPanel (every minute):

```cron
* * * * * /usr/local/bin/php /home/<cpanel_user>/outflits-erp/backend/artisan schedule:run >> /home/<cpanel_user>/logs/outflits-scheduler.log 2>&1
```

## 9) File permissions

```bash
chmod -R 755 ~/outflits-erp/backend
chmod -R 775 ~/outflits-erp/backend/storage ~/outflits-erp/backend/bootstrap/cache
```

## 10) Final checks

- `https://api.yourdomain.com/api/v1/auth/login` responds
- frontend loads and can call API
- offline POS bills queue locally and sync using `/api/v1/sales/offline-sync` when online

## Starter-plan limitations and recommendations

- No process manager (Supervisor/systemd) -> use `QUEUE_CONNECTION=sync` or DB queue with periodic cron worker.
- No Redis typically -> avoid Redis-only queue assumptions.
- For heavy traffic or real-time inventory sync, upgrade to VPS.
