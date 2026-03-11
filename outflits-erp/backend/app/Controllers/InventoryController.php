<?php

namespace App\Controllers;

use App\Models\AiInventoryRecommendation;
use App\Models\InventoryControlSetting;
use App\Models\StockMovement;
use App\Models\StoreInventory;
use App\Services\InventoryAiService;
use App\Services\InventorySystemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class InventoryController
{
    public function __construct(
        private readonly InventoryAiService $inventoryAiService,
        private readonly InventorySystemService $inventorySystemService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $inventory = StoreInventory::query()->with('variant.product')->paginate($request->integer('per_page', 50));
        return response()->json($inventory);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $row = StoreInventory::query()->findOrFail($id);
        $row->update($request->validate(['quantity' => ['required', 'integer', 'min:0']]));
        return response()->json($row->refresh());
    }

    public function recommendations(Request $request): JsonResponse
    {
        if ($request->boolean('refresh')) {
            $this->inventoryAiService->generateRecommendations();
        }

        $data = AiInventoryRecommendation::query()->latest()->paginate(50);
        return response()->json($data);
    }

    public function reviewRecommendation(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'in:approved,rejected,altered_approved'],
            'approved_reorder_qty' => ['nullable', 'integer', 'min:0']
        ]);

        $row = AiInventoryRecommendation::query()->findOrFail($id);
        $row->update($payload + ['reviewed_by' => auth('api')->id()]);

        return response()->json($row->refresh());
    }

    public function adjust(Request $request): JsonResponse
    {
        $data = $request->validate([
            'location_type' => ['required', 'in:store,warehouse'],
            'location_ref' => ['required', 'string', 'max:120'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity_change' => ['required', 'integer', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $result = $this->inventorySystemService->adjustStock($data, auth('api')->id());
            return response()->json($result, 201);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function transfer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_type' => ['required', 'in:store,warehouse'],
            'from_ref' => ['required', 'string', 'max:120'],
            'to_type' => ['required', 'in:store,warehouse'],
            'to_ref' => ['required', 'string', 'max:120'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $transfer = $this->inventorySystemService->transferStock($data, auth('api')->id());
            return response()->json($transfer, 201);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function movements(Request $request): JsonResponse
    {
        $movements = StockMovement::query()
            ->with('variant.product')
            ->when($request->filled('product_variant_id'), fn ($q) => $q->where('product_variant_id', $request->integer('product_variant_id')))
            ->when($request->filled('location_type'), fn ($q) => $q->where('location_type', $request->string('location_type')))
            ->when($request->filled('location_ref'), fn ($q) => $q->where('location_ref', $request->string('location_ref')))
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json($movements);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $rows = InventoryControlSetting::query()
            ->join('product_variants', 'product_variants.id', '=', 'inventory_control_settings.product_variant_id')
            ->leftJoin('store_inventory', 'store_inventory.product_variant_id', '=', 'product_variants.id')
            ->selectRaw('inventory_control_settings.product_variant_id, product_variants.sku, inventory_control_settings.reorder_level, COALESCE(SUM(store_inventory.quantity),0) as current_stock')
            ->groupBy('inventory_control_settings.product_variant_id', 'product_variants.sku', 'inventory_control_settings.reorder_level')
            ->havingRaw('COALESCE(SUM(store_inventory.quantity),0) <= inventory_control_settings.reorder_level')
            ->orderBy('current_stock')
            ->limit($request->integer('limit', 100))
            ->get();

        return response()->json($rows);
    }
}
