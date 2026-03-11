<?php

namespace App\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportController
{
    public function profitAndLoss(): JsonResponse
    {
        $sales = DB::table('sales')->sum('total_amount');
        $expenses = DB::table('expenses')->sum('amount');
        return response()->json(['sales' => $sales, 'expenses' => $expenses, 'net' => $sales - $expenses]);
    }

    public function sellThrough(): JsonResponse
    {
        $rows = DB::table('products')
            ->select('products.id', 'products.name', DB::raw('COALESCE(SUM(sale_items.quantity),0) as sold_units'))
            ->leftJoin('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->leftJoin('sale_items', 'sale_items.product_variant_id', '=', 'product_variants.id')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('sold_units')
            ->limit(100)
            ->get();

        return response()->json($rows);
    }
}
