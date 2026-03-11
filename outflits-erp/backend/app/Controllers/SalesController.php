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
        ]);

        $sale = DB::transaction(function () use ($data) {
            $total = collect($data['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']);
            $sale = Sale::query()->create([
                'store_id' => $data['store_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'payment_method' => $data['payment_method'],
                'total_amount' => $total,
            ]);

            foreach ($data['items'] as $item) {
                $sale->items()->create($item + ['line_total' => $item['quantity'] * $item['unit_price']]);
            }
            return $sale;
        });

        return response()->json($sale->load('items'), 201);
    }
}
