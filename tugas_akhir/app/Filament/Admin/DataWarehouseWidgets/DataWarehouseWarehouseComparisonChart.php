<?php

declare(strict_types=1);

namespace App\Filament\Admin\DataWarehouseWidgets;

use Filament\Widgets\ChartWidget;
use App\Services\DataWarehouse\InventoryBiAnalyticsService;

class DataWarehouseWarehouseComparisonChart extends ChartWidget
{
    protected ?string $heading = 'Perbandingan Pergerakan Inventori per Gudang';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '340px';

    public string $period = 'all';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string|int|null $warehouseId = null;

    public string|int|null $chartYear = '';

    public string|int|null $productId = '';

    public ?string $productCategory = '';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return true;
    }

    protected function getData(): array
    {
        $filters = [
            'period' => $this->period,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'warehouseId' => $this->warehouseId,
            'productId' => $this->productId,
            'productCategory' => $this->productCategory,
        ];

        if ($this->chartYear !== null && $this->chartYear !== '') {
            $filters['period'] = 'year_' . (int) $this->chartYear;
            $filters['startDate'] = null;
            $filters['endDate'] = null;
        }

        $rows = app(InventoryBiAnalyticsService::class)->warehouseComparison($filters);

        return [
            'datasets' => [
                [
                    'label' => 'Qty Masuk',
                    'data' => array_column($rows, 'qty_in'),
                    'backgroundColor' => '#22c55e',
                    'borderColor' => '#16a34a',
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                ],
                [
                    'label' => 'Qty Keluar',
                    'data' => array_column($rows, 'qty_out'),
                    'backgroundColor' => '#f97316',
                    'borderColor' => '#ea580c',
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                ],
                [
                    'label' => 'Pergerakan Bersih',
                    'data' => array_column($rows, 'net_movement'),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#2563eb',
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                ],
            ],
            'labels' => array_column($rows, 'name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Kuantitas',
                    ],
                ],
            ],
        ];
    }
}