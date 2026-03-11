<?php

use Illuminate\Support\Facades\Route;
use App\Controllers\AuthController;
use App\Controllers\ProductController;
use App\Controllers\InventoryController;
use App\Controllers\SalesController;
use App\Controllers\CustomerController;
use App\Controllers\SupplierController;
use App\Controllers\PurchaseController;
use App\Controllers\ReportController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    Route::middleware('auth:api')->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::get('inventory/recommendations', [InventoryController::class, 'recommendations']);
        Route::post('inventory/recommendations/{id}/review', [InventoryController::class, 'reviewRecommendation']);
        Route::apiResource('inventory', InventoryController::class)->only(['index', 'update']);
        Route::apiResource('sales', SalesController::class)->only(['index', 'store', 'show']);
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('purchases', PurchaseController::class);
        Route::get('reports/pl', [ReportController::class, 'profitAndLoss']);
        Route::get('reports/sell-through', [ReportController::class, 'sellThrough']);
    });
});
