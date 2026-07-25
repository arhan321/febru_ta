<?php

namespace App\Filament\Admin\Widgets;

use App\Models\StockBalance;
use App\Models\InboundTransaction;
use App\Models\OutboundTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryOverviewWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Inventory';

    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $totalStock = StockBalance::query()
            ->sum('qty_on_hand');

        $productsWithStock = StockBalance::query()
            ->whereRaw('(qty_on_hand - COALESCE(qty_reserved, 0)) > 0')
            ->count();

        $lowStock = StockBalance::query()
            ->whereRaw('(qty_on_hand - COALESCE(qty_reserved, 0)) > 0')
            ->whereRaw('(qty_on_hand - COALESCE(qty_reserved, 0)) <= minimum_stock')
            ->count();

        $emptyStock = StockBalance::query()
            ->whereRaw('(qty_on_hand - COALESCE(qty_reserved, 0)) <= 0')
            ->count();

        $pendingInbound = InboundTransaction::query()
            ->where('status', 'pending')
            ->count();

        $pendingOutbound = OutboundTransaction::query()
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('Total Stok Fisik', number_format((float) $totalStock, 0, ',', '.'))
                ->description('Akumulasi stok seluruh gudang')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('success'),

            Stat::make('Produk Ada Stok', number_format($productsWithStock, 0, ',', '.'))
                ->description('Produk dengan stok tersedia')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary'),

            Stat::make('Stok Menipis', number_format($lowStock, 0, ',', '.'))
                ->description('Produk perlu perhatian')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Stok Habis', number_format($emptyStock, 0, ',', '.'))
                ->description('Produk tidak tersedia')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Barang Masuk Pending', number_format($pendingInbound, 0, ',', '.'))
                ->description('Menunggu approval admin')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('warning'),

            Stat::make('Barang Keluar Pending', number_format($pendingOutbound, 0, ',', '.'))
                ->description('Menunggu approval admin')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('warning'),
        ];
    }
}