<?php

namespace App\Console\Commands;

use Throwable;
use App\Models\DwEtlRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncAndReconcileInventoryDataWarehouse extends Command
{
    protected $signature = 'dw:sync-and-reconcile';

    protected $description = 'Menjalankan sinkronisasi ETL dan memvalidasi hasilnya melalui rekonsiliasi OLTP–Data Warehouse.';

    public function handle(): int
    {
        $previousRunId = $this->latestEtlRunId();

        $this->info('Tahap 1/2: Menjalankan sinkronisasi Data Warehouse...');

        $syncExitCode = $this->call('dw:sync-inventory', [
            '--trigger' => 'scheduler',
        ]);
        $etlRun = $this->latestEtlRunAfter($previousRunId);

        if ($syncExitCode !== self::SUCCESS) {
            $this->recordReconciliationResult(
                $etlRun,
                DwEtlRun::RECONCILIATION_FAILED,
                'Rekonsiliasi tidak dijalankan karena proses ETL gagal.'
            );

            $this->error('Proses dihentikan karena sinkronisasi ETL gagal.');

            return self::FAILURE;
        }

        if (! $etlRun) {
            $this->error(
                'Proses ETL selesai, tetapi riwayat run baru tidak ditemukan pada tabel dw_etl_runs.'
            );

            return self::FAILURE;
        }

        if (
            strtolower((string) $etlRun->status)
            !== DwEtlRun::STATUS_SUCCESS
        ) {
            $this->recordReconciliationResult(
                $etlRun,
                DwEtlRun::RECONCILIATION_FAILED,
                'Rekonsiliasi tidak dijalankan karena status ETL bukan success.'
            );

            $this->error(
                'Proses dihentikan karena status ETL terbaru bukan success.'
            );

            return self::FAILURE;
        }

        if (! $this->recordReconciliationResult(
            $etlRun,
            DwEtlRun::RECONCILIATION_PENDING,
            'Menunggu proses rekonsiliasi OLTP–Data Warehouse.'
        )) {
            $this->error(
                'Status rekonsiliasi tidak dapat dicatat. Pastikan migration kolom rekonsiliasi sudah dijalankan.'
            );

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Tahap 2/2: Memvalidasi hasil ETL melalui rekonsiliasi...');

        try {
            $reconcileExitCode = $this->call('dw:reconcile-inventory');
        } catch (Throwable $exception) {
            $this->recordReconciliationResult(
                $etlRun,
                DwEtlRun::RECONCILIATION_FAILED,
                'Rekonsiliasi gagal: ' . $exception->getMessage()
            );

            $this->error('Rekonsiliasi gagal: ' . $exception->getMessage());

            report($exception);

            return self::FAILURE;
        }

        if ($reconcileExitCode !== self::SUCCESS) {
            $this->recordReconciliationResult(
                $etlRun,
                DwEtlRun::RECONCILIATION_FAILED,
                'Ditemukan perbedaan antara data OLTP approved dan tabel fakta Data Warehouse.'
            );

            $this->error(
                'ETL berhasil, tetapi hasil rekonsiliasi OLTP–Data Warehouse tidak sinkron.'
            );

            return self::FAILURE;
        }

        if (! $this->recordReconciliationResult(
            $etlRun,
            DwEtlRun::RECONCILIATION_SUCCESS,
            'Data OLTP approved dan Data Warehouse telah dinyatakan sinkron.'
        )) {
            $this->error(
                'Data dinyatakan sinkron, tetapi hasil rekonsiliasi gagal dicatat ke dw_etl_runs.'
            );

            return self::FAILURE;
        }

        $this->newLine();
        $this->info(
            'Sinkronisasi dan rekonsiliasi selesai: data OLTP dan Data Warehouse SINKRON.'
        );

        return self::SUCCESS;
    }

    private function latestEtlRunId(): ?int
    {
        if (! Schema::hasTable('dw_etl_runs')) {
            return null;
        }

        $latestId = DwEtlRun::query()->max('id');

        return $latestId !== null ? (int) $latestId : null;
    }

    private function latestEtlRunAfter(?int $previousRunId): ?DwEtlRun
    {
        if (! Schema::hasTable('dw_etl_runs')) {
            return null;
        }

        return DwEtlRun::query()
            ->when(
                $previousRunId !== null,
                fn ($query) => $query->where('id', '>', $previousRunId)
            )
            ->latest('id')
            ->first();
    }

    private function recordReconciliationResult(
        ?DwEtlRun $etlRun,
        string $status,
        string $message
    ): bool {
        if (
            ! $etlRun
            || ! Schema::hasColumn('dw_etl_runs', 'reconciliation_status')
        ) {
            return false;
        }

        try {
            return $etlRun->forceFill([
                'reconciliation_status' => $status,
                'reconciled_at' => $status === DwEtlRun::RECONCILIATION_PENDING
                    ? null
                    : now(),
                'reconciliation_message' => mb_substr($message, 0, 65535),
            ])->save();
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}