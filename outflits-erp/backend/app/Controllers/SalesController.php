<?php

namespace App\Controllers;

use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(Sale::query()->with(['items.variant', 'customer'])->paginate($request->integer('per_page', 30)));
    }

    public function show(Sale $sale): JsonResponse
    {
        return response()->json($sale->load(['items.variant', 'customer']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'payment_method' => ['required', 'in:cash,upi,card,mixed,reward_points'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'offline_reference' => ['nullable', 'string', 'max:100', 'unique:sales,offline_reference'],
            'sold_at' => ['nullable', 'date'],
            'is_offline_sale' => ['nullable', 'boolean'],
        ]);

        $sale = $this->persistSale($data);

        return response()->json($sale->load('items'), 201);
    }

    public function offlineSync(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'sales' => ['required', 'array', 'min:1'],
            'sales.*.offline_reference' => ['required', 'string', 'max:100'],
            'sales.*.store_id' => ['required', 'exists:stores,id'],
            'sales.*.customer_id' => ['nullable', 'exists:customers,id'],
            'sales.*.payment_method' => ['required', 'in:cash,upi,card,mixed,reward_points'],
            'sales.*.sold_at' => ['nullable', 'date'],
            'sales.*.items' => ['required', 'array', 'min:1'],
            'sales.*.items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'sales.*.items.*.quantity' => ['required', 'integer', 'min:1'],
            'sales.*.items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $synced = [];
        $duplicates = [];

        foreach ($payload['sales'] as $saleData) {
            $existing = Sale::query()->where('offline_reference', $saleData['offline_reference'])->first();
            if ($existing) {
                $duplicates[] = ['offline_reference' => $saleData['offline_reference'], 'sale_id' => $existing->id];
                continue;
            }

            $sale = $this->persistSale($saleData + ['is_offline_sale' => true]);
            $synced[] = ['offline_reference' => $sale->offline_reference, 'sale_id' => $sale->id];
        }

        return response()->json([
            'synced_count' => count($synced),
            'duplicate_count' => count($duplicates),
            'synced' => $synced,
            'duplicates' => $duplicates,
        ]);
    }

    private function persistSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $total = collect($data['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']);
            $sale = Sale::query()->create([
                'offline_reference' => $data['offline_reference'] ?? null,
                'store_id' => $data['store_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'payment_method' => $data['payment_method'],
                'total_amount' => $total,
                'sold_at' => $data['sold_at'] ?? now(),
                'is_offline_sale' => (bool) ($data['is_offline_sale'] ?? false),
            ]);

            foreach ($data['items'] as $item) {
                $sale->items()->create($item + ['line_total' => $item['quantity'] * $item['unit_price']]);
            }

            return $sale;
        });
    }
}
