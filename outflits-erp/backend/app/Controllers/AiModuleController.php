<?php

namespace App\Controllers;

use App\Models\AiDemandForecast;
use App\Models\AiSizeAllocation;
use App\Services\RetailAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiModuleController
{
    public function __construct(private readonly RetailAiService $retailAiService)
    {
    }

    public function generateDemandForecasts(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'period_days' => ['nullable', 'integer', 'min:7', 'max:365'],
        ]);

        $result = $this->retailAiService->generateDemandForecasts($payload['period_days'] ?? 30);

        return response()->json($result, 201);
    }

    public function demandForecasts(Request $request): JsonResponse
    {
        $rows = AiDemandForecast::query()
            ->with('variant.product')
            ->when($request->filled('product_variant_id'), fn ($q) => $q->where('product_variant_id', $request->integer('product_variant_id')))
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json($rows);
    }

    public function generateSizeAllocation(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'recommended_total_qty' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        $allocation = $this->retailAiService->generateSizeAllocation(
            $payload['product_id'],
            $payload['store_id'] ?? null,
            $payload['recommended_total_qty'] ?? 100
        );

        return response()->json($allocation, 201);
    }

    public function sizeAllocations(Request $request): JsonResponse
    {
        $rows = AiSizeAllocation::query()
            ->with(['product', 'store', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json($rows);
    }

    public function reviewSizeAllocation(Request $request, AiSizeAllocation $allocation): JsonResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'in:approved,rejected,altered_approved'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'exists:ai_size_allocation_items,id'],
            'items.*.approved_qty' => ['required_with:items', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($allocation, $payload) {
            if (!empty($payload['items'])) {
                $validItemIds = $allocation->items()->pluck('id')->all();
                foreach ($payload['items'] as $item) {
                    if (!in_array($item['id'], $validItemIds, true)) {
                        continue;
                    }
                    $allocation->items()->where('id', $item['id'])->update(['approved_qty' => $item['approved_qty']]);
                }
            }

            $allocation->update([
                'status' => $payload['status'],
                'reviewed_by' => auth('api')->id(),
                'reviewed_at' => now(),
            ]);
        });

        return response()->json($allocation->refresh()->load('items'));
    }
}
