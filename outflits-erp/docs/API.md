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

## Inventory
- `GET /api/v1/inventory`
- `PUT /api/v1/inventory/{id}`
- `GET /api/v1/inventory/recommendations?refresh=true`
- `POST /api/v1/inventory/recommendations/{id}/review`

## Sales / POS
- `GET /api/v1/sales`
- `POST /api/v1/sales`
- `POST /api/v1/sales/offline-sync`
- `GET /api/v1/sales/{id}`

## Customers
- REST resource `/api/v1/customers`

## Suppliers
- REST resource `/api/v1/suppliers`

## Purchases
- REST resource `/api/v1/purchases`

## Reports
- `GET /api/v1/reports/pl`
- `GET /api/v1/reports/sell-through`


### Offline POS sync payload
`POST /api/v1/sales/offline-sync` accepts: 
- `sales[]` array
- each sale includes `offline_reference`, `store_id`, `payment_method`, `sold_at`, and `items[]`
- each item includes `product_variant_id`, `quantity`, `unit_price`

Server returns `synced_count`, `duplicate_count`, and synced/duplicate references for idempotent local queue cleanup.
