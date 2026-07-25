<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Product;
use Filament\Widgets\Widget;
use App\Models\InboundTransaction;
use App\Models\OutboundTransaction;

class DashboardHeaderWidget extends Widget
{
    protected string $view = 'filament.admin.pages.dashboard-header-widget';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $inboundThisMonth = InboundTransaction::query()
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();

        $outboundThisMonth = OutboundTransaction::query()
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();

        return [
            'today' => now()->translatedFormat('l, d F Y'),
            'totalProducts' => Product::query()->count(),
            'inboundThisMonth' => $inboundThisMonth,
            'outboundThisMonth' => $outboundThisMonth,
        ];
    }
}