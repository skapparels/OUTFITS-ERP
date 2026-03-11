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
- ERP core system (RBAC + masters + settings)
- Fashion model: collection/style/product/variant with statuses
- Product & Style Management APIs (collection/style/variant lifecycle)
- Inventory control settings + AI recommendation workflow
- Inventory system operations (adjustment, transfer, movement ledger, low-stock alerts)
- Warehouse operations (setup, receiving, putaway, picking, packing, dispatch, replenishment)
- POS sales API with payment mode support
- Offline POS billing queue + sync API concept (`sales/offline-sync`)
- Purchase management workflows (supplier mappings, auto PO generation, receiving, purchase returns)
- CRM & loyalty workflows (VIP profiles, visits, recommendations, loyalty ledger)
- HR & payroll workflows (attendance, shifts, leave, overtime, payroll generation)
- AI modules (demand forecasting + size allocation with review workflow)
- Finance and credit foundational schema


## Linux production deployment
Detailed VPS/hosting deployment steps are available in `docs/DEPLOYMENT_LINUX.md` including Nginx, PHP-FPM, Supervisor, systemd scheduler, SSL, and a deployment script.


## GoDaddy Starter deployment
For GoDaddy Web Hosting Starter (cPanel/shared hosting), follow `docs/DEPLOY_GODADDY_STARTER.md` and use `scripts/package_godaddy_starter.sh` to generate an upload-ready package.


## Deploy now (GoDaddy)
Use `docs/DEPLOY_NOW_GODADDY.md` for immediate step-by-step deployment and `scripts/deploy_godaddy_now.sh` for package upload automation.
