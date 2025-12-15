<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SaleReturnController;


/*
|--------------------------------------------------------------------------
| Public API routes
|--------------------------------------------------------------------------
*/

// Token-based login for Next.js users
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected API routes (auth:sanctum via Bearer token)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn (Request $request) => $request->user());

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('products', ProductController::class);

    Route::apiResource('purchases', PurchaseController::class)
        ->only(['index', 'show', 'store']);

    Route::apiResource('sales', SaleController::class)
        ->only(['index', 'show', 'store']);

    Route::get('dashboard/summary', [DashboardController::class, 'summary']);

    Route::get('reports/daily', [ReportController::class, 'daily']);
    Route::get('reports/top-products', [ReportController::class, 'topProducts']);

    Route::get('settings', [SettingController::class, 'show']);
    Route::put('settings', [SettingController::class, 'update']);

    //Return
    Route::post('/sales/{sale}/returns', [SaleReturnController::class, 'store']);
    Route::get('/sales/{sale}/returns', [SaleReturnController::class, 'index']);

});
