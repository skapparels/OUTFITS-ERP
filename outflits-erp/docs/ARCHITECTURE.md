# OUTFLITS ERP Architecture

- Backend: Laravel 11 REST API, JWT auth, queued AI recommendation jobs, MySQL and Redis.
- Frontend: React 18 + Vite + Tailwind-based dashboards for Admin, Store and POS.
- AI Modules:
  - Inventory recommendation engine with admin approval statuses.
  - Size-allocation and demand forecasting hooks can extend `app/Services`.
- Fashion hierarchy: Collection -> Style -> Product(Style SKU family) -> Variant(Color/Size/SKU).
- Multi-location inventory across stores and warehouses.
- Modular domains:
  - Product lifecycle
  - Warehouse and purchasing
  - POS / sales / returns
  - CRM / loyalty
  - HR / finance / credit

- Offline POS concept:
  - bills are queued locally in browser storage when internet/API is unavailable
  - each queued bill carries `offline_reference` for idempotent replay
  - sync API (`/sales/offline-sync`) imports queued bills once connectivity returns
