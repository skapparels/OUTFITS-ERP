<?php

namespace App\Controllers;

use App\Models\AiInventoryRecommendation;
use App\Models\StoreInventory;
use App\Services\InventoryAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController
{
    public function __construct(private readonly InventoryAiService $inventoryAiService)
    {
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
}
