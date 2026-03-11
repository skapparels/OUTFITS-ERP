<?php

namespace App\Services;

use App\Models\AiInventoryRecommendation;
use App\Models\InventoryControlSetting;
use App\Models\ProductVariant;
use App\Models\SaleItem;
use Carbon\Carbon;

class InventoryAiService
{
    public function generateRecommendations(): void
    {
        $variants = ProductVariant::query()->get();

        foreach ($variants as $variant) {
            $sold30 = SaleItem::query()
                ->where('product_variant_id', $variant->id)
                ->whereDate('created_at', '>=', Carbon::now()->subDays(30))
                ->sum('quantity');

            $velocity = $sold30 / 30;
            $setting = InventoryControlSetting::query()->firstOrCreate(
                ['product_variant_id' => $variant->id],
                ['min_stock' => 20, 'max_stock' => 200, 'reorder_level' => 30, 'lead_time_days' => 7]
            );

            $recommended = max((int) ceil(($velocity * $setting->lead_time_days) + $setting->reorder_level), 0);

            AiInventoryRecommendation::query()->create([
                'product_variant_id' => $variant->id,
                'sales_velocity' => $velocity,
                'suggested_reorder_qty' => $recommended,
                'suggested_min_stock' => max((int) floor($velocity * 7), 5),
                'suggested_max_stock' => max((int) ceil($velocity * 45), 20),
                'status' => 'pending',
            ]);
        }
    }
}
