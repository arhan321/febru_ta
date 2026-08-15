<?php

declare(strict_types=1);

namespace App\Services\DataWarehouse;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Support\InventoryBiCalculator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Schema;

final class InventoryBiAnalyticsService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters): array
    {
        $range = $this->dateKeyRange($filters);
        $current = $this->movementTotals($filters, $range);
        $previousRange = $this->previousPeriodRange($range);
        $previous = $previousRange === null
            ? null
            : $this->movementTotals($filters, $previousRange);

        $summary = [
            'qty_in' => $current['qty_in'],
            'qty_out' => $current['qty_out'],
            'net_movement' => $current['qty_in'] - $current['qty_out'],
            'previous_available' => $previous !== null,
            'previous_qty_in' => $previous['qty_in'] ?? null,
            'previous_qty_out' => $previous['qty_out'] ?? null,
            'qty_in_change' => $previous === null
                ? null
                : InventoryBiCalculator::percentageChange($current['qty_in'], $previous['qty_in']),
            'qty_out_change' => $previous === null
                ? null
                : InventoryBiCalculator::percentageChange($current['qty_out'], $previous['qty_out']),
        ];

        $classification = $this->productMovementClassification($filters, $range);
        $warehouses = $this->warehouseComparison($filters, $range);
        $stockAlerts = $this->stockAlerts($filters, $range);

        return [
            'summary' => $summary,
            'classification' => $classification,
            'warehouses' => $warehouses,
            'stock_alerts' => $stockAlerts,
            'insights' => $this->buildInsights($summary, $classification, $warehouses),
            'comparison_note' => $previous === null
                ? 'Perbandingan periode sebelumnya tersedia setelah memilih rentang waktu yang memiliki tanggal awal dan akhir.'
                : 'Dibandingkan dengan rentang waktu sebelumnya yang memiliki jumlah hari sama.',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: int|null, end: int|null}|null  $range
     * @return array<int, array<string, mixed>>
     */
    public function warehouseComparison(array $filters, ?array $range = null): array
    {
        if (! Schema::hasTable('dw_dim_warehouses')) {
            return [];
        }

        $range ??= $this->dateKeyRange($filters);
        $warehouseQuery = DB::table('dw_dim_warehouses as warehouse')
            ->select('warehouse.id', 'warehouse.name');

        if ($this->selectedId($filters['warehouseId'] ?? null) !== null) {
            $warehouseQuery->where('warehouse.id', $this->selectedId($filters['warehouseId']));
        }

        if (Schema::hasTable('dw_fact_inventory_movements')) {
            $movementSummary = DB::table('dw_fact_inventory_movements as movement')
                ->select('movement.warehouse_dim_id')
                ->selectRaw('COALESCE(SUM(movement.qty_in), 0) as qty_in')
                ->selectRaw('COALESCE(SUM(movement.qty_out), 0) as qty_out')
                ->groupBy('movement.warehouse_dim_id');

            $this->applyMovementFilters($movementSummary, $filters, $range);

            $warehouseQuery
                ->leftJoinSub($movementSummary, 'movement_summary', function ($join): void {
                    $join->on('movement_summary.warehouse_dim_id', '=', 'warehouse.id');
                })
                ->addSelect(DB::raw('COALESCE(movement_summary.qty_in, 0) as qty_in'))
                ->addSelect(DB::raw('COALESCE(movement_summary.qty_out, 0) as qty_out'));
        } else {
            $warehouseQuery
                ->addSelect(DB::raw('0 as qty_in'))
                ->addSelect(DB::raw('0 as qty_out'));
        }

        $latestDateKey = $this->latestSnapshotDateKey($filters, $range);

        if ($latestDateKey !== null) {
            $stockSummary = DB::table('dw_fact_stock_snapshots as snapshot')
                ->select('snapshot.warehouse_dim_id')
                ->selectRaw("SUM(CASE WHEN snapshot.stock_status = 'menipis' THEN 1 ELSE 0 END) as low_stock_count")
                ->selectRaw("SUM(CASE WHEN snapshot.stock_status = 'habis' THEN 1 ELSE 0 END) as empty_stock_count")
                ->where('snapshot.date_key', $latestDateKey)
                ->groupBy('snapshot.warehouse_dim_id');

            $this->applySnapshotDimensionFilters($stockSummary, $filters);

            $warehouseQuery
                ->leftJoinSub($stockSummary, 'stock_summary', function ($join): void {
                    $join->on('stock_summary.warehouse_dim_id', '=', 'warehouse.id');
                })
                ->addSelect(DB::raw('COALESCE(stock_summary.low_stock_count, 0) as low_stock_count'))
                ->addSelect(DB::raw('COALESCE(stock_summary.empty_stock_count, 0) as empty_stock_count'));
        } else {
            $warehouseQuery
                ->addSelect(DB::raw('0 as low_stock_count'))
                ->addSelect(DB::raw('0 as empty_stock_count'));
        }

        return $warehouseQuery
            ->get()
            ->map(function ($row): array {
                $qtyIn = (float) $row->qty_in;
                $qtyOut = (float) $row->qty_out;

                return [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'qty_in' => $qtyIn,
                    'qty_out' => $qtyOut,
                    'net_movement' => $qtyIn - $qtyOut,
                    'low_stock_count' => (int) $row->low_stock_count,
                    'empty_stock_count' => (int) $row->empty_stock_count,
                    'attention_count' => (int) $row->low_stock_count + (int) $row->empty_stock_count,
                ];
            })
            ->sortByDesc(fn (array $row): float => $row['qty_in'] + $row['qty_out'])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: int|null, end: int|null}|null  $range
     * @return array{qty_in: float, qty_out: float}
     */
    private function movementTotals(array $filters, ?array $range): array
    {
        if (! Schema::hasTable('dw_fact_inventory_movements')) {
            return ['qty_in' => 0.0, 'qty_out' => 0.0];
        }

        $query = DB::table('dw_fact_inventory_movements as movement')
            ->selectRaw('COALESCE(SUM(movement.qty_in), 0) as qty_in')
            ->selectRaw('COALESCE(SUM(movement.qty_out), 0) as qty_out');

        $this->applyMovementFilters($query, $filters, $range);
        $totals = $query->first();

        return [
            'qty_in' => (float) ($totals->qty_in ?? 0),
            'qty_out' => (float) ($totals->qty_out ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: int|null, end: int|null}|null  $range
     * @return array<string, mixed>
     */
    private function productMovementClassification(array $filters, ?array $range): array
    {
        $empty = [
            'average_active_qty_out' => 0.0,
            'counts' => ['fast' => 0, 'slow' => 0, 'non_moving' => 0],
            'rows' => [],
            'rule' => 'Cepat bergerak berada di atas rata-rata qty keluar produk aktif; lambat bergerak berada di bawah atau sama dengan rata-rata; tidak bergerak tidak memiliki qty keluar pada periode terpilih.',
        ];

        if (! Schema::hasTable('dw_dim_products')) {
            return $empty;
        }

        $productQuery = DB::table('dw_dim_products as product')
            ->select('product.id', 'product.name', 'product.category_name')
            ->where('product.is_active', true);

        if (Schema::hasTable('dw_fact_inventory_movements')) {
            $movementSummary = DB::table('dw_fact_inventory_movements as movement')
                ->select('movement.product_dim_id')
                ->selectRaw('COALESCE(SUM(movement.qty_out), 0) as total_qty_out')
                ->selectRaw('SUM(CASE WHEN movement.qty_out > 0 THEN 1 ELSE 0 END) as movement_frequency')
                ->selectRaw('MAX(CASE WHEN movement.qty_out > 0 THEN movement.date_key ELSE NULL END) as last_movement_date_key')
                ->groupBy('movement.product_dim_id');

            $this->applyMovementFilters($movementSummary, $filters, $range, false);

            $productQuery
                ->leftJoinSub($movementSummary, 'movement_summary', function ($join): void {
                    $join->on('movement_summary.product_dim_id', '=', 'product.id');
                })
                ->addSelect(DB::raw('COALESCE(movement_summary.total_qty_out, 0) as total_qty_out'))
                ->addSelect(DB::raw('COALESCE(movement_summary.movement_frequency, 0) as movement_frequency'))
                ->addSelect('movement_summary.last_movement_date_key');
        } else {
            $productQuery
                ->addSelect(DB::raw('0 as total_qty_out'))
                ->addSelect(DB::raw('0 as movement_frequency'))
                ->addSelect(DB::raw('NULL as last_movement_date_key'));
        }

        $productId = $this->selectedId($filters['productId'] ?? null);

        if ($productId !== null) {
            $productQuery->where('product.id', $productId);
        }

        $category = trim((string) ($filters['productCategory'] ?? ''));

        if ($category !== '') {
            $productQuery->where('product.category_name', $category);
        }

        $rows = $productQuery
            ->orderBy('product.name')
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'category' => $row->category_name ?: '-',
                'total_qty_out' => (float) $row->total_qty_out,
                'movement_frequency' => (int) $row->movement_frequency,
                'last_movement_date' => $this->formatDateKey($row->last_movement_date_key),
            ])
            ->all();

        $classification = InventoryBiCalculator::classifyProductMovements($rows);
        $displayRows = [];

        foreach (['fast', 'slow', 'non_moving'] as $type) {
            $categoryRows = array_values(array_filter(
                $classification['rows'],
                fn (array $row): bool => $row['classification'] === $type,
            ));

            usort($categoryRows, function (array $left, array $right) use ($type): int {
                if ($type === 'non_moving') {
                    return strcasecmp($left['name'], $right['name']);
                }

                return $right['total_qty_out'] <=> $left['total_qty_out'];
            });

            $displayRows = array_merge($displayRows, array_slice($categoryRows, 0, 5));
        }

        $classification['rows'] = $displayRows;
        $classification['rule'] = $empty['rule'];

        return $classification;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: int|null, end: int|null}|null  $range
     * @return array<int, array<string, mixed>>
     */
    private function stockAlerts(array $filters, ?array $range): array
    {
        if (
            ! Schema::hasTable('dw_fact_stock_snapshots') ||
            ! Schema::hasTable('dw_dim_products') ||
            ! Schema::hasTable('dw_dim_warehouses')
        ) {
            return [];
        }

        $latestDateKey = $this->latestSnapshotDateKey($filters, $range);

        if ($latestDateKey === null) {
            return [];
        }

        $query = DB::table('dw_fact_stock_snapshots as snapshot')
            ->join('dw_dim_products as product', 'product.id', '=', 'snapshot.product_dim_id')
            ->join('dw_dim_warehouses as warehouse', 'warehouse.id', '=', 'snapshot.warehouse_dim_id')
            ->select(
                'product.name as product_name',
                'product.category_name',
                'warehouse.name as warehouse_name',
                'snapshot.qty_available',
                'snapshot.minimum_stock',
                'snapshot.stock_status',
                'snapshot.date_key',
            )
            ->where('snapshot.date_key', $latestDateKey)
            ->where(function (Builder $query): void {
                $query
                    ->whereIn('snapshot.stock_status', ['menipis', 'habis'])
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->where('snapshot.minimum_stock', '>', 0)
                            ->whereColumn('snapshot.qty_available', '<=', 'snapshot.minimum_stock');
                    });
            });

        $this->applySnapshotDimensionFilters($query, $filters, 'product');

        return $query
            ->orderByRaw("CASE WHEN snapshot.stock_status = 'habis' THEN 0 ELSE 1 END")
            ->orderBy('snapshot.qty_available')
            ->limit(20)
            ->get()
            ->map(fn ($row): array => [
                'product_name' => (string) $row->product_name,
                'category' => $row->category_name ?: '-',
                'warehouse_name' => (string) $row->warehouse_name,
                'qty_available' => (float) $row->qty_available,
                'minimum_stock' => (float) $row->minimum_stock,
                'shortage' => max(0, (float) $row->minimum_stock - (float) $row->qty_available),
                'status' => (string) $row->stock_status,
                'status_label' => $row->stock_status === 'habis' ? 'Habis' : 'Perlu Perhatian',
                'snapshot_date' => $this->formatDateKey($row->date_key),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: int|null, end: int|null}|null  $range
     */
    private function latestSnapshotDateKey(array $filters, ?array $range): ?int
    {
        if (! Schema::hasTable('dw_fact_stock_snapshots')) {
            return null;
        }

        $query = DB::table('dw_fact_stock_snapshots as snapshot');
        $this->applyDateRange($query, 'snapshot.date_key', $range);
        $this->applySnapshotDimensionFilters($query, $filters);
        $latestDateKey = $query->max('snapshot.date_key');

        return $latestDateKey ? (int) $latestDateKey : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: int|null, end: int|null}|null  $range
     */
    private function applyMovementFilters(
        Builder $query,
        array $filters,
        ?array $range,
        bool $filterCategory = true,
    ): void {
        $this->applyDateRange($query, 'movement.date_key', $range);

        $warehouseId = $this->selectedId($filters['warehouseId'] ?? null);

        if ($warehouseId !== null) {
            $query->where('movement.warehouse_dim_id', $warehouseId);
        }

        $productId = $this->selectedId($filters['productId'] ?? null);

        if ($productId !== null) {
            $query->where('movement.product_dim_id', $productId);
        }

        $category = trim((string) ($filters['productCategory'] ?? ''));

        if ($filterCategory && $category !== '' && Schema::hasTable('dw_dim_products')) {
            $query
                ->join('dw_dim_products as product_filter', 'product_filter.id', '=', 'movement.product_dim_id')
                ->where('product_filter.category_name', $category);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applySnapshotDimensionFilters(
        Builder $query,
        array $filters,
        ?string $joinedProductAlias = null,
    ): void {
        $warehouseId = $this->selectedId($filters['warehouseId'] ?? null);

        if ($warehouseId !== null) {
            $query->where('snapshot.warehouse_dim_id', $warehouseId);
        }

        $productId = $this->selectedId($filters['productId'] ?? null);

        if ($productId !== null) {
            $query->where('snapshot.product_dim_id', $productId);
        }

        $category = trim((string) ($filters['productCategory'] ?? ''));

        if ($category === '' || ! Schema::hasTable('dw_dim_products')) {
            return;
        }

        if ($joinedProductAlias !== null) {
            $query->where($joinedProductAlias . '.category_name', $category);

            return;
        }

        $query
            ->join('dw_dim_products as snapshot_product_filter', 'snapshot_product_filter.id', '=', 'snapshot.product_dim_id')
            ->where('snapshot_product_filter.category_name', $category);
    }

    /**
     * @param  array{start: int|null, end: int|null}|null  $range
     */
    private function applyDateRange(Builder $query, string $column, ?array $range): void
    {
        if ($range === null) {
            return;
        }

        if ($range['start'] !== null && $range['end'] !== null) {
            $query->whereBetween($column, [$range['start'], $range['end']]);

            return;
        }

        if ($range['start'] !== null) {
            $query->where($column, '>=', $range['start']);
        }

        if ($range['end'] !== null) {
            $query->where($column, '<=', $range['end']);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{start: int|null, end: int|null}|null
     */
    private function dateKeyRange(array $filters): ?array
    {
        $startDate = $filters['startDate'] ?? null;
        $endDate = $filters['endDate'] ?? null;

        if ($startDate || $endDate) {
            return [
                'start' => $startDate ? (int) Carbon::parse($startDate)->format('Ymd') : null,
                'end' => $endDate ? (int) Carbon::parse($endDate)->format('Ymd') : null,
            ];
        }

        $period = (string) ($filters['period'] ?? 'all');

        if ($period === 'all') {
            return null;
        }

        if (str_starts_with($period, 'year_')) {
            $year = (int) str_replace('year_', '', $period);

            return [
                'start' => (int) Carbon::create($year, 1, 1)->startOfYear()->format('Ymd'),
                'end' => (int) Carbon::create($year, 12, 31)->endOfYear()->format('Ymd'),
            ];
        }

        $now = now();
        [$start, $end] = match ($period) {
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [null, null],
        };

        if ($start === null || $end === null) {
            return null;
        }

        return [
            'start' => (int) $start->format('Ymd'),
            'end' => (int) $end->format('Ymd'),
        ];
    }

    /**
     * @param  array{start: int|null, end: int|null}|null  $range
     * @return array{start: int, end: int}|null
     */
    private function previousPeriodRange(?array $range): ?array
    {
        if ($range === null || $range['start'] === null || $range['end'] === null) {
            return null;
        }

        $start = Carbon::createFromFormat('Ymd', (string) $range['start'])->startOfDay();
        $end = Carbon::createFromFormat('Ymd', (string) $range['end'])->startOfDay();

        if ($start->greaterThan($end)) {
            return null;
        }

        $numberOfDays = (int) $start->diffInDays($end) + 1;
        $previousEnd = $start->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays($numberOfDays - 1);

        return [
            'start' => (int) $previousStart->format('Ymd'),
            'end' => (int) $previousEnd->format('Ymd'),
        ];
    }

    private function selectedId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function formatDateKey(mixed $dateKey): string
    {
        if (! $dateKey) {
            return '-';
        }

        return Carbon::createFromFormat('Ymd', (string) $dateKey)->translatedFormat('d M Y');
    }

    /**
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $classification
     * @param  array<int, array<string, mixed>>  $warehouses
     * @return array<int, array{title: string, body: string, tone: string, icon: string}>
     */
    private function buildInsights(
        array $summary,
        array $classification,
        array $warehouses,
    ): array {
        $netMovement = (float) $summary['net_movement'];

        if ($netMovement > 0) {
            $movementBody = 'Barang masuk lebih besar ' . $this->formatQuantity($netMovement) . ' unit dibandingkan barang keluar pada periode terpilih.';
            $movementTone = 'positive';
        } elseif ($netMovement < 0) {
            $movementBody = 'Barang keluar lebih besar ' . $this->formatQuantity(abs($netMovement)) . ' unit dibandingkan barang masuk pada periode terpilih.';
            $movementTone = 'warning';
        } else {
            $movementBody = 'Jumlah barang masuk dan keluar seimbang pada periode terpilih.';
            $movementTone = 'neutral';
        }

        $comparisonBody = 'Pilih periode dengan tanggal awal dan akhir untuk melihat perubahan terhadap periode sebelumnya.';
        $comparisonTone = 'info';

        if ($summary['previous_available']) {
            $change = $summary['qty_out_change'];

            if ($change === null) {
                $comparisonBody = 'Periode sebelumnya belum memiliki qty keluar sehingga persentase perubahan belum dapat dihitung.';
            } elseif ($change > 0) {
                $comparisonBody = 'Qty keluar meningkat ' . number_format(abs($change), 1, ',', '.') . '% dibandingkan periode sebelumnya.';
            } elseif ($change < 0) {
                $comparisonBody = 'Qty keluar menurun ' . number_format(abs($change), 1, ',', '.') . '% dibandingkan periode sebelumnya.';
            } else {
                $comparisonBody = 'Qty keluar tidak berubah dibandingkan periode sebelumnya.';
            }
        }

        $busiestWarehouse = collect($warehouses)->sortByDesc('qty_out')->first();
        $warehouseBody = $busiestWarehouse && $busiestWarehouse['qty_out'] > 0
            ? $busiestWarehouse['name'] . ' mencatat qty keluar tertinggi, yaitu ' . $this->formatQuantity($busiestWarehouse['qty_out']) . ' unit.'
            : 'Belum terdapat pergerakan keluar per gudang pada periode terpilih.';

        $attentionCount = array_sum(array_column($warehouses, 'attention_count'));
        $stockBody = $attentionCount > 0
            ? $attentionCount . ' item pada snapshot terakhir perlu diperiksa karena stok menipis atau habis.'
            : 'Tidak ada item berstatus menipis atau habis pada snapshot terakhir dalam filter terpilih.';

        return [
            ['title' => 'Arah Pergerakan', 'body' => $movementBody, 'tone' => $movementTone, 'icon' => '↕'],
            ['title' => 'Perbandingan Periode', 'body' => $comparisonBody, 'tone' => $comparisonTone, 'icon' => '↗'],
            ['title' => 'Aktivitas Gudang', 'body' => $warehouseBody, 'tone' => 'info', 'icon' => '▦'],
            [
                'title' => 'Perhatian Stok',
                'body' => $stockBody . ' Produk tidak bergerak: ' . number_format((int) $classification['counts']['non_moving'], 0, ',', '.') . '.',
                'tone' => $attentionCount > 0 ? 'danger' : 'positive',
                'icon' => '!',
            ],
        ];
    }

    private function formatQuantity(float $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}