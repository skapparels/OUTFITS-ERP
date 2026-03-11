<?php

namespace App\Controllers;

use App\Models\StoreInventory;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Models\WarehouseOperation;
use App\Models\WarehouseZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WarehouseController
{
    public function index(Request $request): JsonResponse
    {
        $warehouses = Warehouse::query()
            ->withCount(['zones', 'operations'])
            ->paginate($request->integer('per_page', 30));

        return response()->json($warehouses);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $warehouse = Warehouse::query()->create($payload);
        return response()->json($warehouse, 201);
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        return response()->json($warehouse->load(['zones.racks']));
    }

    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $warehouse->update($payload);

        return response()->json($warehouse->refresh());
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $warehouse->delete();
        return response()->json([], 204);
    }

    public function addZone(Request $request, Warehouse $warehouse): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        $zone = $warehouse->zones()->create($payload);
        return response()->json($zone, 201);
    }

    public function addRack(Request $request, Warehouse $warehouse, WarehouseZone $zone): JsonResponse
    {
        if ($zone->warehouse_id !== $warehouse->id) {
            return response()->json(['message' => 'Zone does not belong to the selected warehouse.'], 422);
        }

        $payload = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'capacity_units' => ['nullable', 'integer', 'min:0'],
        ]);

        $rack = $zone->racks()->create($payload);
        return response()->json($rack, 201);
    }

    public function receive(Request $request, Warehouse $warehouse): JsonResponse
    {
        $payload = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'zone_code' => ['nullable', 'string', 'max:40'],
            'rack_code' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->increaseWarehouseStock($warehouse, $payload, 'receiving');
    }

    public function putaway(Request $request, Warehouse $warehouse): JsonResponse
    {
        $payload = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'zone_code' => ['required', 'string', 'max:40'],
            'rack_code' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->increaseWarehouseStock($warehouse, $payload, 'putaway');
    }

    public function pick(Request $request, Warehouse $warehouse): JsonResponse
    {
        $payload = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'zone_code' => ['nullable', 'string', 'max:40'],
            'rack_code' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->decreaseWarehouseStock($warehouse, $payload, 'picking');
    }

    public function pack(Request $request, Warehouse $warehouse): JsonResponse
    {
        $payload = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $operation = $this->recordOperation($warehouse->id, [
            'operation_type' => 'packing',
            'product_variant_id' => $payload['product_variant_id'],
            'quantity' => $payload['quantity'],
            'notes' => $payload['notes'] ?? null,
        ]);

        return response()->json($operation, 201);
    }

    public function dispatch(Request $request, Warehouse $warehouse): JsonResponse
    {
        $payload = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $result = DB::transaction(function () use ($warehouse, $payload) {
                $inventory = WarehouseInventory::query()
                    ->where('warehouse_code', $warehouse->code)
                    ->where('product_variant_id', $payload['product_variant_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$inventory || $inventory->quantity < $payload['quantity']) {
                    throw new RuntimeException('Insufficient warehouse stock for dispatch.');
                }

                $inventory->decrement('quantity', $payload['quantity']);

                if (!empty($payload['store_id'])) {
                    $storeRow = StoreInventory::query()->firstOrCreate(
                        ['store_id' => $payload['store_id'], 'product_variant_id' => $payload['product_variant_id']],
                        ['quantity' => 0]
                    );
                    $storeRow->increment('quantity', $payload['quantity']);
                }

                return $this->recordOperation($warehouse->id, [
                    'operation_type' => 'dispatch',
                    'product_variant_id' => $payload['product_variant_id'],
                    'quantity' => $payload['quantity'],
                    'store_id' => $payload['store_id'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                ]);
            });
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result, 201);
    }

    public function replenishStore(Request $request, Warehouse $warehouse): JsonResponse
    {
        $payload = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $request->merge([
            'store_id' => $payload['store_id'],
            'product_variant_id' => $payload['product_variant_id'],
            'quantity' => $payload['quantity'],
            'notes' => $payload['notes'] ?? 'Store replenishment',
        ]);

        $response = $this->dispatch($request, $warehouse);

        if ($response->status() >= 400) {
            return $response;
        }

        $operation = $this->recordOperation($warehouse->id, [
            'operation_type' => 'replenishment',
            'product_variant_id' => $payload['product_variant_id'],
            'quantity' => $payload['quantity'],
            'store_id' => $payload['store_id'],
            'notes' => $payload['notes'] ?? null,
        ]);

        return response()->json($operation, 201);
    }

    public function operations(Request $request, Warehouse $warehouse): JsonResponse
    {
        $rows = $warehouse->operations()
            ->with(['variant.product', 'store'])
            ->when($request->filled('operation_type'), fn ($q) => $q->where('operation_type', $request->string('operation_type')))
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json($rows);
    }

    private function increaseWarehouseStock(Warehouse $warehouse, array $payload, string $operationType): JsonResponse
    {
        $result = DB::transaction(function () use ($warehouse, $payload, $operationType) {
            $row = WarehouseInventory::query()->firstOrCreate(
                ['warehouse_code' => $warehouse->code, 'product_variant_id' => $payload['product_variant_id']],
                ['zone' => $payload['zone_code'] ?? null, 'rack' => $payload['rack_code'] ?? null, 'quantity' => 0]
            );

            $updates = [];
            if (!empty($payload['zone_code'])) {
                $updates['zone'] = $payload['zone_code'];
            }
            if (!empty($payload['rack_code'])) {
                $updates['rack'] = $payload['rack_code'];
            }
            if ($updates) {
                $row->update($updates);
            }

            $row->increment('quantity', $payload['quantity']);

            return $this->recordOperation($warehouse->id, [
                'operation_type' => $operationType,
                'product_variant_id' => $payload['product_variant_id'],
                'quantity' => $payload['quantity'],
                'zone_code' => $payload['zone_code'] ?? null,
                'rack_code' => $payload['rack_code'] ?? null,
                'notes' => $payload['notes'] ?? null,
            ]);
        });

        return response()->json($result, 201);
    }

    private function decreaseWarehouseStock(Warehouse $warehouse, array $payload, string $operationType): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($warehouse, $payload, $operationType) {
                $row = WarehouseInventory::query()
                    ->where('warehouse_code', $warehouse->code)
                    ->where('product_variant_id', $payload['product_variant_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$row || $row->quantity < $payload['quantity']) {
                    throw new RuntimeException('Insufficient warehouse stock for this operation.');
                }

                $row->decrement('quantity', $payload['quantity']);

                return $this->recordOperation($warehouse->id, [
                    'operation_type' => $operationType,
                    'product_variant_id' => $payload['product_variant_id'],
                    'quantity' => $payload['quantity'],
                    'zone_code' => $payload['zone_code'] ?? null,
                    'rack_code' => $payload['rack_code'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                ]);
            });
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result, 201);
    }

    private function recordOperation(int $warehouseId, array $payload): WarehouseOperation
    {
        return WarehouseOperation::query()->create($payload + [
            'warehouse_id' => $warehouseId,
            'performed_by' => auth('api')->id(),
        ]);
    }
}
