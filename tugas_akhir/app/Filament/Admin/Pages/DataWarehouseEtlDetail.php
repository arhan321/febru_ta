<?php

namespace App\Filament\Admin\Pages;

use App\Models\DwEtlRun;
use Filament\Pages\Page;

class DataWarehouseEtlDetail extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Detail Proses ETL';

    protected string $view = 'filament.admin.pages.data-warehouse-etl-detail';

    public int $runId;

    public function mount(): void
    {
        $this->runId = (int) request()->query('run', 0);

        abort_if($this->runId <= 0, 404);
    }

    public function getEtlRun(): DwEtlRun
    {
        return DwEtlRun::query()
            ->with([
                'triggeredByUser',
                'details',
            ])
            ->findOrFail($this->runId);
    }
}