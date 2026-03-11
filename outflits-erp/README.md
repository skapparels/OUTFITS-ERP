# OUTFLITS ERP Monorepo

Production-oriented scaffold for a fashion retail ERP supporting stores, franchises, warehouses, POS, AI inventory intelligence, and lifecycle management.

## Structure
- `backend/` Laravel 11 API services
- `frontend/` React 18 dashboards + POS
- `database/` SQL references
- `docs/` architecture + API docs
- `docker/` local development containers

## Quick start
1. `cd outflits-erp/docker && docker compose up -d`
2. Backend: install Composer deps and run migrations in `backend/`
3. Frontend: `cd ../frontend && npm install && npm run dev`

## Core capabilities included
- JWT-based auth API
- Fashion model: collection/style/product/variant with statuses
- Inventory control settings + AI recommendation workflow
- POS sales API with payment mode support
- Offline POS billing queue + sync API concept (`sales/offline-sync`)
- Supplier and purchase order foundations
- Customer + loyalty foundations
- HR, finance, and credit foundational schema


## Linux production deployment
Detailed VPS/hosting deployment steps are available in `docs/DEPLOYMENT_LINUX.md` including Nginx, PHP-FPM, Supervisor, systemd scheduler, SSL, and a deployment script.


## GoDaddy Starter deployment
For GoDaddy Web Hosting Starter (cPanel/shared hosting), follow `docs/DEPLOY_GODADDY_STARTER.md` and use `scripts/package_godaddy_starter.sh` to generate an upload-ready package.
