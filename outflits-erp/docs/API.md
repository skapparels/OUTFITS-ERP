# OUTFLITS ERP API

## Auth
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`

## Products
- `GET /api/v1/products`
- `POST /api/v1/products`
- `GET /api/v1/products/{id}`
- `PUT /api/v1/products/{id}`
- `DELETE /api/v1/products/{id}`

## Product & Style Management
- `GET /api/v1/collections`
- `POST /api/v1/collections`
- `GET /api/v1/styles`
- `POST /api/v1/styles`
- `POST /api/v1/styles/{style}/move-to-clearance`
- `GET /api/v1/variants`
- `POST /api/v1/variants`


## ERP Core System
- `GET /api/v1/stores`
- `POST /api/v1/stores`
- `GET /api/v1/warehouses`
- `POST /api/v1/warehouses`
- `POST /api/v1/warehouses/{warehouse}/zones`
- `POST /api/v1/warehouses/{warehouse}/zones/{zone}/racks`
- `POST /api/v1/warehouses/{warehouse}/receive`
- `POST /api/v1/warehouses/{warehouse}/putaway`
- `POST /api/v1/warehouses/{warehouse}/pick`
- `POST /api/v1/warehouses/{warehouse}/pack`
- `POST /api/v1/warehouses/{warehouse}/dispatch`
- `POST /api/v1/warehouses/{warehouse}/replenish`
- `GET /api/v1/warehouses/{warehouse}/operations`
- `GET /api/v1/franchises`
- `POST /api/v1/franchises`
- `GET /api/v1/roles`
- `POST /api/v1/roles`
- `GET /api/v1/permissions`
- `POST /api/v1/roles/{role}/permissions`
- `POST /api/v1/roles/assign-user`
- `GET /api/v1/system-settings`
- `POST /api/v1/system-settings`

## Inventory
- `GET /api/v1/inventory`
- `PUT /api/v1/inventory/{id}`
- `GET /api/v1/inventory/recommendations?refresh=true`
- `POST /api/v1/inventory/recommendations/{id}/review`
- `GET /api/v1/inventory/movements`
- `GET /api/v1/inventory/low-stock`
- `POST /api/v1/inventory/adjust`
- `POST /api/v1/inventory/transfer`

## Sales / POS
- `GET /api/v1/sales`
- `POST /api/v1/sales`
- `POST /api/v1/sales/offline-sync`
- `GET /api/v1/sales/{id}`

## Customers
- REST resource `/api/v1/customers`
- `POST /api/v1/customers/{customer}/visits`
- `POST /api/v1/customers/{customer}/recommendations`
- `POST /api/v1/customers/{customer}/loyalty/adjust`
- `GET /api/v1/customers/{customer}/loyalty/ledger`
- `GET /api/v1/customers/{customer}/behavior-summary`

### CRM & Loyalty flow
- Customer profile supports VIP flag, preferences, lifetime value, and last visit tracking.
- Visit tracking endpoint logs store/channel interactions for clienteling timelines.
- Recommendation endpoint stores product suggestions with acceptance lifecycle.
- Loyalty adjustment endpoint writes ledger entries and updates reward point balance.
- Behavior summary provides quick CRM insight (visits, accepted recommendations, value).

## HR & Payroll
- `GET /api/v1/hr/staff`
- `POST /api/v1/hr/staff`
- `GET /api/v1/hr/staff/{staff}`
- `PUT /api/v1/hr/staff/{staff}`
- `DELETE /api/v1/hr/staff/{staff}`
- `POST /api/v1/hr/attendance/mark`
- `GET /api/v1/hr/attendance/report`
- `POST /api/v1/hr/shifts/assign`
- `POST /api/v1/hr/leaves/request`
- `POST /api/v1/hr/leaves/{leave}/review`
- `POST /api/v1/hr/tasks`
- `POST /api/v1/hr/overtime`
- `POST /api/v1/hr/overtime/{entry}/review`
- `POST /api/v1/hr/payroll/generate`
- `GET /api/v1/hr/payroll`

### HR & Payroll flow
- Staff master supports employee code, base salary, and employment status.
- Attendance marking + reporting for daily workforce tracking.
- Shift assignment and leave request/review workflow.
- Staff task assignment and overtime approval workflow.
- Payroll generator computes gross/net using base salary + approved overtime.

## AI Modules
- `POST /api/v1/ai/demand-forecasts/generate`
- `GET /api/v1/ai/demand-forecasts`
- `POST /api/v1/ai/size-allocations/generate`
- `GET /api/v1/ai/size-allocations`
- `POST /api/v1/ai/size-allocations/{allocation}/review`

### AI modules flow
- Demand forecasting computes sales-velocity forecasts per variant with confidence scoring.
- Size allocation engine builds size curve percentages from historic sales for each product.
- Admin reviews size allocation and can approve/reject/alter with per-size approved quantities.

## Suppliers
- REST resource `/api/v1/suppliers`

## Purchases
- REST resource `/api/v1/purchases`
- `POST /api/v1/purchases/auto-generate`
- `POST /api/v1/purchases/supplier-mappings`
- `POST /api/v1/purchases/{purchase}/receive`
- `POST /api/v1/purchases/{purchase}/returns`

### Purchase workflow
- Supplier-product mapping stores preferred vendor, MOQ, cost, and lead time.
- `auto-generate` builds draft POs from reorder-level breaches using inventory control settings.
- `receive` books incoming stock into store/warehouse inventory and updates per-line received quantity.
- `returns` creates purchase return notes and decrements stock with return quantity checks.

## Reports
- `GET /api/v1/reports/pl`
- `GET /api/v1/reports/sell-through`


### Offline POS sync payload
`POST /api/v1/sales/offline-sync` accepts: 
- `sales[]` array
- each sale includes `offline_reference`, `store_id`, `payment_method`, `sold_at`, and `items[]`
- each item includes `product_variant_id`, `quantity`, `unit_price`

Server returns `synced_count`, `duplicate_count`, and synced/duplicate references for idempotent local queue cleanup.


### Style lifecycle flow
- Collection -> Style -> Product -> Variant(Color/Size/SKU)
- Style statuses: `active`, `inactive`, `clearance`, `discontinued`
- Use `POST /api/v1/styles/{style}/move-to-clearance` to transition styles for sell-through operations.


### Inventory operations flow
- Manual adjustments: `POST /api/v1/inventory/adjust` (supports store/warehouse)
- Transfers: `POST /api/v1/inventory/transfer` (source to destination with movement ledger)
- Ledger: `GET /api/v1/inventory/movements`
- Alerts: `GET /api/v1/inventory/low-stock`

### Warehouse operations flow
- Setup: warehouse -> zone -> rack master records
- Inbound: `receive` and `putaway`
- Outbound: `pick`, `pack`, `dispatch`
- Store replenishment: `replenish` moves stock to store inventory
- Audit: `GET /api/v1/warehouses/{warehouse}/operations`
