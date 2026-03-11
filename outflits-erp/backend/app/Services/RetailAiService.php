<?php

namespace App\Services;

use App\Models\AiDemandForecast;
use App\Models\AiSizeAllocation;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class RetailAiService
{
    public function generateDemandForecasts(int $periodDays = 30): array
    {
        $variants = ProductVariant::query()->get(['id', 'sku']);
        $created = 0;

        foreach ($variants as $variant) {
            $soldQty = SaleItem::query()
                ->where('product_variant_id', $variant->id)
                ->whereDate('created_at', '>=', now()->subDays($periodDays))
                ->sum('quantity');

            $dailyAvg = $periodDays > 0 ? ($soldQty / $periodDays) : 0;
            $forecastQty = round($dailyAvg * $periodDays, 2);

            AiDemandForecast::query()->create([
                'product_variant_id' => $variant->id,
                'period_days' => $periodDays,
                'forecast_qty' => $forecastQty,
                'confidence_score' => $soldQty > 0 ? min(95, 50 + ($soldQty / 2)) : 35,
                'meta' => [
                    'daily_avg' => round($dailyAvg, 2),
                    'source' => 'sales_velocity',
                ],
            ]);

            $created++;
        }

        return ['created_count' => $created, 'period_days' => $periodDays];
    }

    public function generateSizeAllocation(int $productId, ?int $storeId = null, int $recommendedTotalQty = 100): AiSizeAllocation
    {
        $product = Product::query()->findOrFail($productId);

        $sizeSales = SaleItem::query()
            ->join('product_variants', 'product_variants.id', '=', 'sale_items.product_variant_id')
            ->where('product_variants.product_id', $product->id)
            ->selectRaw('product_variants.size as size, SUM(sale_items.quantity) as qty')
            ->groupBy('product_variants.size')
            ->pluck('qty', 'size');

        $sizes = ProductVariant::query()
            ->where('product_id', $product->id)
            ->select('size')
            ->distinct()
            ->pluck('size')
            ->values();

        $totalQty = max(1, (int) $sizeSales->sum());

        return DB::transaction(function () use ($product, $storeId, $recommendedTotalQty, $sizes, $sizeSales, $totalQty) {
            $allocation = AiSizeAllocation::query()->create([
                'product_id' => $product->id,
                'store_id' => $storeId,
                'recommended_total_qty' => $recommendedTotalQty,
                'status' => 'pending',
                'meta' => ['source' => 'size_curve_from_sales'],
            ]);

            foreach ($sizes as $size) {
                $sold = (int) ($sizeSales[$size] ?? 0);
                $percentage = $sold > 0 ? round(($sold / $totalQty) * 100, 2) : round(100 / max(1, count($sizes)), 2);
                $recommendedQty = (int) max(1, round(($percentage / 100) * $recommendedTotalQty));

                $allocation->items()->create([
                    'size' => $size,
                    'demand_percentage' => $percentage,
                    'recommended_qty' => $recommendedQty,
                ]);
            }

            return $allocation->load('items');
        });
    }
}
