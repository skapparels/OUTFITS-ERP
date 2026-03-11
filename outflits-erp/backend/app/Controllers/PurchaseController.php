<?php

namespace App\Controllers;

use App\Models\InventoryControlSetting;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\StoreInventory;
use App\Models\SupplierProductMapping;
use App\Models\WarehouseInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseController
{
    public function index(Request $request): JsonResponse
    {
        $orders = PurchaseOrder::query()
            ->with(['supplier', 'items.variant'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->latest()
            ->paginate($request->integer('per_page', 30));

        return response()->json($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'destination_type' => ['required', 'in:store,warehouse'],
            'destination_ref' => ['required', 'string', 'max:120'],
            'status' => ['nullable', 'in:draft,approved,received,closed'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $order = DB::transaction(function () use ($payload) {
            $order = PurchaseOrder::query()->create([
                'order_number' => $this->nextOrderNumber(),
                'supplier_id' => $payload['supplier_id'],
                'order_date' => $payload['order_date'],
                'expected_delivery_date' => $payload['expected_delivery_date'] ?? null,
                'destination_type' => $payload['destination_type'],
                'destination_ref' => $payload['destination_ref'],
                'status' => $payload['status'] ?? 'draft',
                'notes' => $payload['notes'] ?? null,
                'total_amount' => 0,
                'approved_at' => ($payload['status'] ?? 'draft') === 'approved' ? now() : null,
            ]);

            $total = 0;
            foreach ($payload['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_cost'];
                $total += $lineTotal;

                $order->items()->create([
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                ]);
            }

            $order->update(['total_amount' => $total]);
            return $order;
        });

        return response()->json($order->load(['supplier', 'items.variant']), 201);
    }

    public function show(PurchaseOrder $purchase): JsonResponse
    {
        return response()->json($purchase->load(['supplier', 'items.variant', 'returns.items']));
    }

    public function update(Request $request, PurchaseOrder $purchase): JsonResponse
    {
        if (in_array($purchase->status, ['received', 'closed'], true)) {
            return response()->json(['message' => 'Cannot edit a received/closed purchase order.'], 422);
        }

        $payload = $request->validate([
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'destination_type' => ['sometimes', 'in:store,warehouse'],
            'destination_ref' => ['sometimes', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'in:draft,approved'],
        ]);

        if (($payload['status'] ?? null) === 'approved' && empty($purchase->approved_at)) {
            $payload['approved_at'] = now();
        }

        $purchase->update($payload);

        return response()->json($purchase->refresh()->load(['supplier', 'items.variant']));
    }

    public function destroy(PurchaseOrder $purchase): JsonResponse
    {
        if ($purchase->status !== 'draft') {
            return response()->json(['message' => 'Only draft purchase orders can be deleted.'], 422);
        }

        $purchase->delete();
        return response()->json([], 204);
    }

    public function receive(Request $request, PurchaseOrder $purchase): JsonResponse
    {
        if (!in_array($purchase->status, ['approved', 'received'], true)) {
            return response()->json(['message' => 'Only approved purchase orders can be received.'], 422);
        }

        $payload = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.received_quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($purchase, $payload) {
                $expectedItems = $purchase->items()->get()->keyBy('id');

                foreach ($payload['items'] as $row) {
                    /** @var PurchaseOrderItem $item */
                    $item = $expectedItems->get($row['purchase_order_item_id']);
                    if (!$item) {
                        throw new RuntimeException('Purchase order item does not belong to this order.');
                    }

                    $newReceivedQty = $item->received_quantity + $row['received_quantity'];
                    if ($newReceivedQty > $item->quantity) {
                        throw new RuntimeException('Received quantity cannot exceed ordered quantity.');
                    }

                    $item->update(['received_quantity' => $newReceivedQty]);

                    $this->incrementDestinationStock(
                        $purchase->destination_type,
                        $purchase->destination_ref,
                        $item->product_variant_id,
                        $row['received_quantity']
                    );
                }

                $allReceived = $purchase->items()->whereColumn('received_quantity', '<', 'quantity')->doesntExist();
                $purchase->update([
                    'status' => $allReceived ? 'received' : 'approved',
                    'received_at' => now(),
                ]);
            });
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($purchase->refresh()->load(['items.variant']));
    }

    public function autoGenerate(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'destination_type' => ['required', 'in:store,warehouse'],
            'destination_ref' => ['required', 'string', 'max:120'],
        ]);

        $variants = InventoryControlSetting::query()
            ->join('product_variants', 'product_variants.id', '=', 'inventory_control_settings.product_variant_id')
            ->leftJoin('supplier_product_mappings', function ($join) {
                $join->on('supplier_product_mappings.product_variant_id', '=', 'product_variants.id')
                    ->where('supplier_product_mappings.is_preferred', true);
            })
            ->selectRaw('inventory_control_settings.*, product_variants.preferred_supplier_id, supplier_product_mappings.supplier_id as mapped_supplier_id, supplier_product_mappings.supplier_cost, supplier_product_mappings.minimum_order_quantity')
            ->get();

        $groupedBySupplier = [];

        foreach ($variants as $row) {
            $currentStock = StoreInventory::query()->where('product_variant_id', $row->product_variant_id)->sum('quantity')
                + WarehouseInventory::query()->where('product_variant_id', $row->product_variant_id)->sum('quantity');

            if ($currentStock > $row->reorder_level) {
                continue;
            }

            $supplierId = $row->mapped_supplier_id ?: $row->preferred_supplier_id;
            if (!$supplierId) {
                continue;
            }

            $suggestedQty = max(
                $row->max_stock - $currentStock,
                (int) ($row->minimum_order_quantity ?? 1)
            );

            if ($suggestedQty <= 0) {
                continue;
            }

            $groupedBySupplier[$supplierId][] = [
                'product_variant_id' => $row->product_variant_id,
                'quantity' => $suggestedQty,
                'unit_cost' => (float) ($row->supplier_cost ?? 0),
            ];
        }

        $created = [];

        DB::transaction(function () use (&$created, $groupedBySupplier, $payload) {
            foreach ($groupedBySupplier as $supplierId => $items) {
                $order = PurchaseOrder::query()->create([
                    'order_number' => $this->nextOrderNumber(),
                    'supplier_id' => $supplierId,
                    'order_date' => now()->toDateString(),
                    'destination_type' => $payload['destination_type'],
                    'destination_ref' => $payload['destination_ref'],
                    'status' => 'draft',
                    'total_amount' => 0,
                ]);

                $total = 0;
                foreach ($items as $item) {
                    $total += ($item['quantity'] * $item['unit_cost']);
                    $order->items()->create($item);
                }

                $order->update(['total_amount' => $total]);
                $created[] = $order->load('items');
            }
        });

        return response()->json([
            'created_count' => count($created),
            'orders' => $created,
        ]);
    }

    public function addSupplierMapping(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'supplier_cost' => ['required', 'numeric', 'min:0'],
            'supplier_lead_time_days' => ['nullable', 'integer', 'min:0'],
            'minimum_order_quantity' => ['nullable', 'integer', 'min:1'],
            'is_preferred' => ['nullable', 'boolean'],
        ]);

        if (!empty($payload['is_preferred'])) {
            SupplierProductMapping::query()
                ->where('product_variant_id', $payload['product_variant_id'])
                ->update(['is_preferred' => false]);
        }

        $mapping = SupplierProductMapping::query()->updateOrCreate(
            [
                'supplier_id' => $payload['supplier_id'],
                'product_variant_id' => $payload['product_variant_id'],
            ],
            [
                'supplier_cost' => $payload['supplier_cost'],
                'supplier_lead_time_days' => $payload['supplier_lead_time_days'] ?? 0,
                'minimum_order_quantity' => $payload['minimum_order_quantity'] ?? 1,
                'is_preferred' => $payload['is_preferred'] ?? false,
            ]
        );

        return response()->json($mapping->load(['supplier', 'variant']), 201);
    }

    public function createReturn(Request $request, PurchaseOrder $purchase): JsonResponse
    {
        if (!in_array($purchase->status, ['received', 'closed'], true)) {
            return response()->json(['message' => 'Only received purchase orders can be returned.'], 422);
        }

        $payload = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $return = DB::transaction(function () use ($payload, $purchase) {
                $purchaseReturn = PurchaseReturn::query()->create([
                    'purchase_order_id' => $purchase->id,
                    'return_number' => 'PR-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                    'return_date' => now()->toDateString(),
                    'status' => 'processed',
                    'total_amount' => 0,
                    'reason' => $payload['reason'] ?? null,
                ]);

                $orderItems = $purchase->items()->get()->keyBy('id');
                $total = 0;

                foreach ($payload['items'] as $row) {
                    /** @var PurchaseOrderItem $orderItem */
                    $orderItem = $orderItems->get($row['purchase_order_item_id']);
                    if (!$orderItem) {
                        throw new RuntimeException('Return item does not belong to this purchase order.');
                    }

                    $returnableQty = $orderItem->received_quantity - $orderItem->returned_quantity;
                    if ($row['quantity'] > $returnableQty) {
                        throw new RuntimeException('Return quantity exceeds available received quantity.');
                    }

                    $lineTotal = $row['quantity'] * $orderItem->unit_cost;
                    $total += $lineTotal;

                    $purchaseReturn->items()->create([
                        'purchase_order_item_id' => $orderItem->id,
                        'quantity' => $row['quantity'],
                        'unit_cost' => $orderItem->unit_cost,
                    ]);

                    $orderItem->increment('returned_quantity', $row['quantity']);

                    $this->decrementDestinationStock(
                        $purchase->destination_type,
                        $purchase->destination_ref,
                        $orderItem->product_variant_id,
                        $row['quantity']
                    );
                }

                $purchaseReturn->update(['total_amount' => $total]);
                return $purchaseReturn;
            });
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($return->load('items.purchaseOrderItem'), 201);
    }

    private function incrementDestinationStock(string $locationType, string $locationRef, int $variantId, int $qty): void
    {
        if ($locationType === 'store') {
            $row = StoreInventory::query()->firstOrCreate(
                ['store_id' => $locationRef, 'product_variant_id' => $variantId],
                ['quantity' => 0]
            );
            $row->increment('quantity', $qty);
            return;
        }

        $row = WarehouseInventory::query()->firstOrCreate(
            ['warehouse_code' => $locationRef, 'product_variant_id' => $variantId],
            ['quantity' => 0]
        );
        $row->increment('quantity', $qty);
    }

    private function decrementDestinationStock(string $locationType, string $locationRef, int $variantId, int $qty): void
    {
        if ($locationType === 'store') {
            $row = StoreInventory::query()->where('store_id', $locationRef)->where('product_variant_id', $variantId)->first();
            if (!$row || $row->quantity < $qty) {
                throw new RuntimeException('Insufficient stock for purchase return.');
            }
            $row->decrement('quantity', $qty);
            return;
        }

        $row = WarehouseInventory::query()->where('warehouse_code', $locationRef)->where('product_variant_id', $variantId)->first();
        if (!$row || $row->quantity < $qty) {
            throw new RuntimeException('Insufficient stock for purchase return.');
        }
        $row->decrement('quantity', $qty);
    }

    private function nextOrderNumber(): string
    {
        return 'PO-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
