<?php

use Illuminate\Support\Facades\Route;
use App\Controllers\AuthController;
use App\Controllers\CollectionController;
use App\Controllers\FranchiseController;
use App\Controllers\ProductController;
use App\Controllers\InventoryController;
use App\Controllers\ProductVariantController;
use App\Controllers\RoleController;
use App\Controllers\SalesController;
use App\Controllers\CustomerController;
use App\Controllers\StoreController;
use App\Controllers\StyleController;
use App\Controllers\SupplierController;
use App\Controllers\PurchaseController;
use App\Controllers\WarehouseController;
use App\Controllers\HrPayrollController;
use App\Controllers\AiModuleController;
use App\Controllers\ReportController;
use App\Controllers\SystemSettingController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    Route::middleware('auth:api')->group(function () {
        Route::apiResource('collections', CollectionController::class);
        Route::apiResource('styles', StyleController::class);
        Route::post('styles/{style}/move-to-clearance', [StyleController::class, 'moveToClearance']);
        Route::apiResource('variants', ProductVariantController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('stores', StoreController::class);
        Route::apiResource('warehouses', WarehouseController::class);
        Route::post('warehouses/{warehouse}/zones', [WarehouseController::class, 'addZone']);
        Route::post('warehouses/{warehouse}/zones/{zone}/racks', [WarehouseController::class, 'addRack']);
        Route::post('warehouses/{warehouse}/receive', [WarehouseController::class, 'receive']);
        Route::post('warehouses/{warehouse}/putaway', [WarehouseController::class, 'putaway']);
        Route::post('warehouses/{warehouse}/pick', [WarehouseController::class, 'pick']);
        Route::post('warehouses/{warehouse}/pack', [WarehouseController::class, 'pack']);
        Route::post('warehouses/{warehouse}/dispatch', [WarehouseController::class, 'dispatch']);
        Route::post('warehouses/{warehouse}/replenish', [WarehouseController::class, 'replenishStore']);
        Route::get('warehouses/{warehouse}/operations', [WarehouseController::class, 'operations']);
        Route::apiResource('franchises', FranchiseController::class);
        Route::get('roles', [RoleController::class, 'index']);
        Route::post('roles', [RoleController::class, 'store']);
        Route::get('permissions', [RoleController::class, 'permissions']);
        Route::post('roles/{role}/permissions', [RoleController::class, 'assignPermissions']);
        Route::post('roles/assign-user', [RoleController::class, 'assignRoleToUser']);

        Route::get('system-settings', [SystemSettingController::class, 'index']);
        Route::post('system-settings', [SystemSettingController::class, 'upsert']);

        Route::get('inventory/recommendations', [InventoryController::class, 'recommendations']);
        Route::post('inventory/recommendations/{id}/review', [InventoryController::class, 'reviewRecommendation']);
        Route::get('inventory/movements', [InventoryController::class, 'movements']);
        Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);
        Route::post('inventory/adjust', [InventoryController::class, 'adjust']);
        Route::post('inventory/transfer', [InventoryController::class, 'transfer']);
        Route::apiResource('inventory', InventoryController::class)->only(['index', 'update']);
        Route::post('sales/offline-sync', [SalesController::class, 'offlineSync']);
        Route::apiResource('sales', SalesController::class)->only(['index', 'store', 'show']);
        Route::apiResource('customers', CustomerController::class);
        Route::post('customers/{customer}/visits', [CustomerController::class, 'addVisit']);
        Route::post('customers/{customer}/recommendations', [CustomerController::class, 'addRecommendation']);
        Route::post('customers/{customer}/loyalty/adjust', [CustomerController::class, 'adjustLoyalty']);
        Route::get('customers/{customer}/loyalty/ledger', [CustomerController::class, 'loyaltyLedger']);
        Route::get('customers/{customer}/behavior-summary', [CustomerController::class, 'behaviorSummary']);

        Route::get('hr/staff', [HrPayrollController::class, 'staffIndex']);
        Route::post('hr/staff', [HrPayrollController::class, 'staffStore']);
        Route::get('hr/staff/{staff}', [HrPayrollController::class, 'staffShow']);
        Route::put('hr/staff/{staff}', [HrPayrollController::class, 'staffUpdate']);
        Route::delete('hr/staff/{staff}', [HrPayrollController::class, 'staffDestroy']);
        Route::post('hr/attendance/mark', [HrPayrollController::class, 'markAttendance']);
        Route::get('hr/attendance/report', [HrPayrollController::class, 'attendanceReport']);
        Route::post('hr/shifts/assign', [HrPayrollController::class, 'assignShift']);
        Route::post('hr/leaves/request', [HrPayrollController::class, 'requestLeave']);
        Route::post('hr/leaves/{leave}/review', [HrPayrollController::class, 'reviewLeave']);
        Route::post('hr/tasks', [HrPayrollController::class, 'addTask']);
        Route::post('hr/overtime', [HrPayrollController::class, 'addOvertime']);
        Route::post('hr/overtime/{entry}/review', [HrPayrollController::class, 'reviewOvertime']);
        Route::post('hr/payroll/generate', [HrPayrollController::class, 'generatePayroll']);
        Route::get('hr/payroll', [HrPayrollController::class, 'payrollIndex']);

        Route::post('ai/demand-forecasts/generate', [AiModuleController::class, 'generateDemandForecasts']);
        Route::get('ai/demand-forecasts', [AiModuleController::class, 'demandForecasts']);
        Route::post('ai/size-allocations/generate', [AiModuleController::class, 'generateSizeAllocation']);
        Route::get('ai/size-allocations', [AiModuleController::class, 'sizeAllocations']);
        Route::post('ai/size-allocations/{allocation}/review', [AiModuleController::class, 'reviewSizeAllocation']);

        Route::apiResource('suppliers', SupplierController::class);
        Route::post('purchases/auto-generate', [PurchaseController::class, 'autoGenerate']);
        Route::post('purchases/supplier-mappings', [PurchaseController::class, 'addSupplierMapping']);
        Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
        Route::post('purchases/{purchase}/returns', [PurchaseController::class, 'createReturn']);
        Route::apiResource('purchases', PurchaseController::class);
        Route::get('reports/pl', [ReportController::class, 'profitAndLoss']);
        Route::get('reports/sell-through', [ReportController::class, 'sellThrough']);
    });
});
