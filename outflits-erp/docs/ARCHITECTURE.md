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

- ERP Core System module:
  - RBAC foundation with role-user and permission-role pivots
  - Master data APIs for stores and franchises
  - Centralized `system_settings` key-value registry

- Product & Style Management module:
  - Hierarchy: Collection -> Style -> Product -> Variant(Color/Size/SKU)
  - Style lifecycle API with clearance transition endpoint
  - Variant management with SKU/barcode and inventory thresholds

- Inventory System module:
  - Per-location stock control for stores and warehouses
  - Transactional stock adjustments and transfers
  - Stock movement ledger for audit and reconciliation
  - Low-stock API derived from reorder levels

- Purchase Management module:
  - Supplier-product mapping for preferred vendor, MOQ, cost, and lead-time
  - Auto-purchase generation from reorder-level signals
  - Purchase receiving flow updates inventory by destination
  - Purchase return workflow with stock reversal safeguards

- Warehouse Management module:
  - Warehouse master setup with zone and rack topology
  - Operational events for receiving, putaway, picking, packing, and dispatch
  - Store replenishment from warehouse to store inventory
  - Warehouse operation ledger for traceability and audits

- CRM & Loyalty module:
  - Rich customer profiles with VIP and preference attributes
  - Customer visit tracking for clienteling timelines
  - Product recommendation tracking and response statuses
  - Loyalty points ledger with balance adjustments and behavior summaries

- HR & Payroll module:
  - Staff master, employment status, and base salary setup
  - Daily attendance and shift scheduling workflows
  - Leave request approval and staff task assignment
  - Overtime approval integrated into payroll generation

- AI Modules:
  - Demand forecasting per variant based on sales velocity windows
  - Size allocation engine generating store/product size curves
  - Admin review workflow for allocation approval/rejection/alteration
