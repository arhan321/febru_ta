<?php

namespace App\Filament\Admin\DataWarehouseWidgets;

use Throwable;
use Carbon\Carbon;
use App\Models\DwEtlRun;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DataWarehouseEtlInfoWidget extends StatsOverviewWidget
{
    protected ?string $heading = null;

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return true;
    }

    protected function getStats(): array
    {
        $latestRun = $this->latestEtlRun();
        $runAt = $this->runTimestamp($latestRun);
        $status = strtolower((string) ($latestRun->status ?? ''));
        $statusDetails = $this->statusDetails($status, $latestRun);
        $dimensionRows = (int) ($latestRun->dimension_rows ?? 0);
        $factRows = (int) ($latestRun->fact_rows ?? 0);
        $totalRows = $dimensionRows + $factRows;

        return [
            Stat::make(
                'Terakhir Sinkronisasi ETL',
                $runAt ? $runAt->format('d M Y H:i:s') : '-'
            )
                ->description($runAt
                    ? $runAt->diffForHumans()
                    : 'Belum ada riwayat proses ETL'
                )
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($runAt ? 'info' : 'gray'),

            Stat::make(
                'Status ETL',
                $statusDetails['label']
            )
                ->description($statusDetails['description'])
                ->descriptionIcon($statusDetails['icon'])
                ->color($statusDetails['color']),

            Stat::make(
                'Durasi Proses ETL',
                $this->formatDuration(
                    isset($latestRun->duration_ms)
                        ? (int) $latestRun->duration_ms
                        : null
                )
            )
                ->description($status === DwEtlRun::STATUS_RUNNING
                    ? 'Proses sinkronisasi masih berlangsung'
                    : 'Waktu penyelesaian run ETL terbaru'
                )
                ->descriptionIcon('heroicon-m-clock')
                ->color($status === DwEtlRun::STATUS_RUNNING ? 'warning' : 'primary'),

            Stat::make(
                'Data Hasil ETL',
                sprintf(
                    '%s dimensi / %s fakta',
                    number_format($dimensionRows, 0, ',', '.'),
                    number_format($factRows, 0, ',', '.')
                )
            )
                ->description($latestRun
                    ? sprintf(
                        'Total %s baris pada run terbaru',
                        number_format($totalRows, 0, ',', '.')
                    )
                    : 'Belum ada hasil ETL yang tercatat'
                )
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color($latestRun ? 'primary' : 'gray'),
        ];
    }

    private function latestEtlRun(): ?DwEtlRun
    {
        if (! Schema::hasTable('dw_etl_runs')) {
            return null;
        }

        return DwEtlRun::query()
            ->latest('id')
            ->first();
    }

    private function runTimestamp(?DwEtlRun $latestRun): ?Carbon
    {
        if (! $latestRun) {
            return null;
        }

        $value = $latestRun->finished_at
            ?? $latestRun->started_at
            ?? null;

        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{label: string, description: string, icon: string, color: string}
     */
    private function statusDetails(string $status, ?DwEtlRun $latestRun): array
    {
        return match ($status) {
            DwEtlRun::STATUS_SUCCESS => [
                'label' => 'Berhasil',
                'description' => 'Sinkronisasi selesai tanpa error',
                'icon' => 'heroicon-m-check-circle',
                'color' => 'success',
            ],
            DwEtlRun::STATUS_FAILED => [
                'label' => 'Gagal',
                'description' => $this->failureDescription($latestRun),
                'icon' => 'heroicon-m-x-circle',
                'color' => 'danger',
            ],
            DwEtlRun::STATUS_RUNNING => [
                'label' => 'Sedang Berjalan',
                'description' => 'Sinkronisasi data warehouse sedang diproses',
                'icon' => 'heroicon-m-arrow-path',
                'color' => 'warning',
            ],
            default => [
                'label' => 'Belum Ada Riwayat',
                'description' => 'Jalankan sinkronisasi ETL terlebih dahulu',
                'icon' => 'heroicon-m-exclamation-triangle',
                'color' => 'gray',
            ],
        };
    }

    private function failureDescription(?DwEtlRun $latestRun): string
    {
        $message = trim((string) ($latestRun->error_message ?? ''));

        if ($message === '') {
            return 'Proses ETL berhenti karena terjadi kesalahan';
        }

        return 'Error: ' . Str::limit($message, 120);
    }

    private function formatDuration(?int $milliseconds): string
    {
        if ($milliseconds === null) {
            return '-';
        }

        if ($milliseconds < 1000) {
            return number_format($milliseconds, 0, ',', '.') . ' ms';
        }

        return number_format($milliseconds / 1000, 2, ',', '.') . ' detik';
    }
}