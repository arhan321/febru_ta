<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mobile\MobileAuthController;
use App\Http\Controllers\Api\Mobile\MobileStockController;
use App\Http\Controllers\Api\Mobile\MobileInboundController;
use App\Http\Controllers\Api\Mobile\MobileProfileController;
use App\Http\Controllers\Api\Mobile\MobileOutboundController;
use App\Http\Controllers\Api\Mobile\MobileDashboardController;
use App\Http\Controllers\Api\Mobile\MobileMasterDataController;
use App\Http\Controllers\Api\Mobile\MobileTransactionHistoryController;

Route::prefix('mobile/v1')->group(function (): void {
    Route::post('/login', [MobileAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [MobileAuthController::class, 'logout']);

        Route::get('/profile', [MobileProfileController::class, 'show']);
        Route::post('/profile/change-password', [MobileProfileController::class, 'changePassword']);
        
        Route::get('/master/warehouses', [MobileMasterDataController::class, 'warehouses']);
        Route::get('/master/products', [MobileMasterDataController::class, 'products']);
        Route::get('/master/suppliers', [MobileMasterDataController::class, 'suppliers']);
        Route::get('/master/customers', [MobileMasterDataController::class, 'customers']);

        Route::get('/stocks', [MobileStockController::class, 'index']);

        //Routes Barang Masuk
        Route::get('/inbounds', [MobileInboundController::class, 'index']);
        Route::post('/inbounds', [MobileInboundController::class, 'store']);
        Route::get('/inbounds/{id}', [MobileInboundController::class, 'show']);
        Route::put('/inbounds/{id}', [MobileInboundController::class, 'update']);
        Route::patch('/inbounds/{id}', [MobileInboundController::class, 'update']);

        //Routes Barang Keluar
        Route::get('/outbounds', [MobileOutboundController::class, 'index']);
        Route::post('/outbounds', [MobileOutboundController::class, 'store']);
        Route::get('/outbounds/{id}', [MobileOutboundController::class, 'show']);
        Route::put('/outbounds/{id}', [MobileOutboundController::class, 'update']);
        Route::patch('/outbounds/{id}', [MobileOutboundController::class, 'update']);

        //Routes Riwayat Transaksi
        Route::get('/transactions/history', [MobileTransactionHistoryController::class, 'index']);

        Route::get('/dashboard', [MobileDashboardController::class, 'index']);
    });
});