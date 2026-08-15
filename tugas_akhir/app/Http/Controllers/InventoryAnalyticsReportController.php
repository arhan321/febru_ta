<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\DataWarehouse\InventoryBiAnalyticsService;

final class InventoryAnalyticsReportController extends Controller
{
    public function __invoke(Request $request, InventoryBiAnalyticsService $analyticsService)
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'regex:/^(all|day|week|month|year|year_[0-9]{4})$/'],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date'],
            'warehouseId' => ['nullable', 'integer', 'min:1'],
            'productId' => ['nullable', 'integer', 'min:1'],
            'productCategory' => ['nullable', 'string', 'max:255'],
        ]);

        $filters = [
            'period' => (string) ($validated['period'] ?? 'all'),
            'startDate' => $validated['startDate'] ?? null,
            'endDate' => $validated['endDate'] ?? null,
            'warehouseId' => $validated['warehouseId'] ?? null,
            'productId' => $validated['productId'] ?? null,
            'productCategory' => $validated['productCategory'] ?? null,
        ];

        $report = [
            'analytics' => $analyticsService->dashboard($filters),
            'periodLabel' => $this->periodLabel($filters),
            'warehouseLabel' => $this->warehouseLabel($filters['warehouseId']),
            'productLabel' => $this->productLabel($filters['productId']),
            'categoryLabel' => $filters['productCategory'] ?: 'Semua Kategori',
            'lastSyncAt' => $this->lastSyncAt(),
            'generatedAt' => now(),
        ];

        return Pdf::loadView('reports.inventory-analytics-pdf', $report)
            ->setPaper('a4', 'portrait')
            ->download('laporan-analitik-inventori-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function periodLabel(array $filters): string
    {
        $startDate = $filters['startDate'] ?? null;
        $endDate = $filters['endDate'] ?? null;

        if ($startDate && $endDate) {
            return Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y');
        }

        if ($startDate) {
            return 'Mulai ' . Carbon::parse($startDate)->format('d M Y');
        }

        if ($endDate) {
            return 'Sampai ' . Carbon::parse($endDate)->format('d M Y');
        }

        $period = (string) ($filters['period'] ?? 'all');

        if (str_starts_with($period, 'year_')) {
            return 'Tahun ' . str_replace('year_', '', $period);
        }

        return match ($period) {
            'day' => 'Hari Ini',
            'week' => 'Minggu Ini',
            'month' => 'Bulan Ini',
            'year' => 'Tahun Ini',
            default => 'Seluruh Periode',
        };
    }

    private function warehouseLabel(mixed $warehouseId): string
    {
        if (! $warehouseId || ! Schema::hasTable('dw_dim_warehouses')) {
            return 'Seluruh Gudang';
        }

        return (string) (DB::table('dw_dim_warehouses')
            ->where('id', (int) $warehouseId)
            ->value('name') ?? 'Gudang Tidak Ditemukan');
    }

    private function productLabel(mixed $productId): string
    {
        if (! $productId || ! Schema::hasTable('dw_dim_products')) {
            return 'Semua Produk';
        }

        return (string) (DB::table('dw_dim_products')
            ->where('id', (int) $productId)
            ->value('name') ?? 'Produk Tidak Ditemukan');
    }

    private function lastSyncAt(): ?Carbon
    {
        $latest = null;

        foreach ([
            'dw_fact_inventory_movements',
            'dw_fact_inbound_transactions',
            'dw_fact_outbound_transactions',
            'dw_fact_stock_snapshots',
            'dw_dim_products',
            'dw_dim_warehouses',
            'dw_dim_dates',
        ] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'updated_at')) {
                continue;
            }

            $value = DB::table($table)->max('updated_at');

            if (! $value) {
                continue;
            }

            $date = Carbon::parse($value);

            if (! $latest || $date->greaterThan($latest)) {
                $latest = $date;
            }
        }

        return $latest;
    }
}