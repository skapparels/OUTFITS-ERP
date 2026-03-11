# OUTFLITS ERP Deployment Guide (Linux Web Hosting)

This guide is for Ubuntu 22.04/24.04 VPS or dedicated Linux hosting.

## 1) Server prerequisites

Install required packages:

```bash
sudo apt update
sudo apt install -y nginx mysql-server redis-server supervisor git unzip curl
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-bcmath php8.3-zip php8.3-intl
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

Install Composer:

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
```

## 2) Folder layout (recommended)

```text
/var/www/outflits-erp/
  backend/
  frontend/
  shared/
    storage/
    .env
```

## 3) Clone and prepare code

```bash
sudo mkdir -p /var/www/outflits-erp
sudo chown -R $USER:$USER /var/www/outflits-erp
git clone <your-repo-url> /var/www/outflits-erp
cd /var/www/outflits-erp/outflits-erp
```

## 4) Backend (Laravel API) deployment

```bash
cd backend
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan jwt:secret
```

Update `.env` for production (DB/Redis/APP_URL/CORS/queue).

Run migrations and optimize:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Set permissions:

```bash
sudo chown -R www-data:www-data /var/www/outflits-erp/outflits-erp/backend
sudo find /var/www/outflits-erp/outflits-erp/backend -type f -exec chmod 644 {} \;
sudo find /var/www/outflits-erp/outflits-erp/backend -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/outflits-erp/outflits-erp/backend/storage /var/www/outflits-erp/outflits-erp/backend/bootstrap/cache
```

## 5) Frontend (React + Vite) deployment

```bash
cd ../frontend
npm ci
npm run build
```

Deploy static files from `frontend/dist` to a public web root, e.g.:

```bash
sudo mkdir -p /var/www/outflits-frontend
sudo rsync -av --delete dist/ /var/www/outflits-frontend/
```

## 6) Configure Nginx

- API vhost template: `backend/deploy/nginx-api.conf`
- Frontend vhost template: `frontend/deploy/nginx-frontend.conf`

Install templates:

```bash
sudo cp /var/www/outflits-erp/outflits-erp/backend/deploy/nginx-api.conf /etc/nginx/sites-available/outflits-api
sudo cp /var/www/outflits-erp/outflits-erp/frontend/deploy/nginx-frontend.conf /etc/nginx/sites-available/outflits-frontend
sudo ln -s /etc/nginx/sites-available/outflits-api /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/outflits-frontend /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 7) Queue worker and scheduler

Supervisor for queue worker:

```bash
sudo cp /var/www/outflits-erp/outflits-erp/backend/deploy/supervisor-outflits-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start outflits-worker:*
```

Systemd scheduler service:

```bash
sudo cp /var/www/outflits-erp/outflits-erp/backend/deploy/outflits-scheduler.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now outflits-scheduler
```

## 8) SSL (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d api.example.com -d app.example.com
```

## 9) CI/CD style deployment command

Use included script:

```bash
cd /var/www/outflits-erp/outflits-erp
bash scripts/deploy_linux.sh
```

The script does `git pull`, composer install, migrate, cache refresh, frontend build sync, and worker restart.

## 10) Health checks

```bash
systemctl status nginx
systemctl status php8.3-fpm
sudo supervisorctl status
curl -I https://api.example.com/api/v1/auth/login
curl -I https://app.example.com
```

## Shared hosting note

If your host does not allow Supervisor/systemd:
- use cron for `php artisan schedule:run` every minute
- use hosting panel queue runner or fallback to `QUEUE_CONNECTION=sync` temporarily
- point document root to `backend/public` for API and deploy `frontend/dist` as static files
