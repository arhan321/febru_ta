<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryAnalyticsReportController;

Route::get('/', function () {
    return redirect('/admin');
});
Route::middleware('auth')->get(
    '/admin/reports/inventory-analytics.pdf',
    InventoryAnalyticsReportController::class
)->name('reports.inventory-analytics.pdf');