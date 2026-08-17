<?php

namespace App\Services\DataWarehouse;

use Throwable;
use Carbon\Carbon;
use InvalidArgumentException;
use App\Models\DwEtlRunDetail;


class InventoryEtlMonitoringService
{
    private const STEPS = [
        'dim_products' => [
            'name' => 'Dimensi Produk',
            'type' => 'dimension',
            'order' => 1,
        ],
        'dim_warehouses' => [
            'name' => 'Dimensi Gudang',
            'type' => 'dimension',
            'order' => 2,
        ],
        'dim_suppliers' => [
            'name' => 'Dimensi Supplier',
            'type' => 'dimension',
            'order' => 3,
        ],
        'dim_customers' => [
            'name' => 'Dimensi Pelanggan',
            'type' => 'dimension',
            'order' => 4,
        ],
        'dim_users' => [
            'name' => 'Dimensi Pengguna',
            'type' => 'dimension',
            'order' => 5,
        ],
        'fact_inbound' => [
            'name' => 'Fakta Barang Masuk',
            'type' => 'fact',
            'order' => 6,
        ],
        'fact_outbound' => [
            'name' => 'Fakta Barang Keluar',
            'type' => 'fact',
            'order' => 7,
        ],
        'fact_inventory_movements' => [
            'name' => 'Fakta Pergerakan Inventori',
            'type' => 'fact',
            'order' => 8,
        ],
        'fact_stock_snapshots' => [
            'name' => 'Snapshot Stok',
            'type' => 'fact',
            'order' => 9,
        ],
    ];

    private ?int $etlRunId = null;

    private ?string $currentStepKey = null;

    private array $stepStates = [];

    public function beginRun(?int $etlRunId): void
    {
        $this->etlRunId = $etlRunId;
        $this->currentStepKey = null;
        $this->stepStates = [];
    }

    public function startStep(
        string $stepKey,
        int $sourceRows = 0
    ): void {
        if ($this->etlRunId === null) {
            return;
        }

        $definition = $this->stepDefinition($stepKey);
        $startedAt = now();

        $state = [
            'dw_etl_run_id' => $this->etlRunId,
            'step_key' => $stepKey,
            'step_name' => $definition['name'],
            'step_type' => $definition['type'],
            'step_order' => $definition['order'],
            'status' => DwEtlRunDetail::STATUS_PROCESSING,
            'source_rows' => max(0, $sourceRows),
            'target_rows' => 0,
            'started_at' => $startedAt,
            'finished_at' => null,
            'duration_ms' => null,
            'error_message' => null,
            'rolled_back' => false,
        ];

        $this->currentStepKey = $stepKey;
        $this->stepStates[$stepKey] = $state;

        $this->persistState($stepKey);
    }

    public function completeStep(
        string $stepKey,
        int $targetRows = 0
    ): void {
        if (
            $this->etlRunId === null
            || ! isset($this->stepStates[$stepKey])
        ) {
            return;
        }

        $finishedAt = now();
        $startedAt = $this->stepStates[$stepKey]['started_at'];

        $this->stepStates[$stepKey]['status']
            = DwEtlRunDetail::STATUS_SUCCESS;

        $this->stepStates[$stepKey]['target_rows']
            = max(0, $targetRows);

        $this->stepStates[$stepKey]['finished_at']
            = $finishedAt;

        $this->stepStates[$stepKey]['duration_ms']
            = $this->durationInMilliseconds($startedAt, $finishedAt);

        $this->stepStates[$stepKey]['error_message']
            = null;

        $this->stepStates[$stepKey]['rolled_back']
            = false;

        $this->persistState($stepKey);

        if ($this->currentStepKey === $stepKey) {
            $this->currentStepKey = null;
        }
    }

    public function failRun(Throwable $exception): void
    {
        if ($this->etlRunId === null) {
            return;
        }

        $failedStepKey = $this->currentStepKey;
        $finishedAt = now();

        foreach (self::STEPS as $stepKey => $definition) {
            if ($stepKey === $failedStepKey) {
                $state = $this->stepStates[$stepKey] ?? [
                    'dw_etl_run_id' => $this->etlRunId,
                    'step_key' => $stepKey,
                    'step_name' => $definition['name'],
                    'step_type' => $definition['type'],
                    'step_order' => $definition['order'],
                    'source_rows' => 0,
                    'target_rows' => 0,
                    'started_at' => $finishedAt,
                ];

                $state['status'] = DwEtlRunDetail::STATUS_FAILED;
                $state['finished_at'] = $finishedAt;
                $state['duration_ms'] = $this->durationInMilliseconds(
                    $state['started_at'],
                    $finishedAt
                );
                $state['error_message'] = $exception->getMessage();
                $state['rolled_back'] = false;

                $this->stepStates[$stepKey] = $state;

                continue;
            }

            if (isset($this->stepStates[$stepKey])) {
                $state = $this->stepStates[$stepKey];

                if (
                    $state['status']
                    === DwEtlRunDetail::STATUS_SUCCESS
                ) {
                    $state['status']
                        = DwEtlRunDetail::STATUS_ROLLED_BACK;

                    $state['rolled_back'] = true;
                }

                $this->stepStates[$stepKey] = $state;

                continue;
            }

            $this->stepStates[$stepKey] = [
                'dw_etl_run_id' => $this->etlRunId,
                'step_key' => $stepKey,
                'step_name' => $definition['name'],
                'step_type' => $definition['type'],
                'step_order' => $definition['order'],
                'status' => DwEtlRunDetail::STATUS_SKIPPED,
                'source_rows' => 0,
                'target_rows' => 0,
                'started_at' => null,
                'finished_at' => null,
                'duration_ms' => null,
                'error_message' => null,
                'rolled_back' => false,
            ];
        }

        /*
         * Penting:
         * Method ini dipanggil SETELAH transaksi ETL gagal dan rollback.
         * Karena itu semua state ditulis ulang agar riwayat monitoring
         * tidak ikut hilang akibat rollback transaksi ETL.
         */
        foreach (array_keys(self::STEPS) as $stepKey) {
            $this->persistState($stepKey);
        }

        $this->currentStepKey = null;
    }

    public function sourceRowsTotal(): int
    {
        return array_sum(
            array_column($this->stepStates, 'source_rows')
        );
    }

    public function targetRowsTotal(): int
    {
        return array_sum(
            array_column($this->stepStates, 'target_rows')
        );
    }

    private function persistState(string $stepKey): void
    {
        if (
            $this->etlRunId === null
            || ! isset($this->stepStates[$stepKey])
        ) {
            return;
        }

        $state = $this->stepStates[$stepKey];

        DwEtlRunDetail::updateOrCreate(
            [
                'dw_etl_run_id' => $this->etlRunId,
                'step_key' => $stepKey,
            ],
            $state
        );
    }

    private function stepDefinition(string $stepKey): array
    {
        if (! isset(self::STEPS[$stepKey])) {
            throw new InvalidArgumentException(
                "Tahap ETL tidak dikenal: {$stepKey}"
            );
        }

        return self::STEPS[$stepKey];
    }

    private function durationInMilliseconds(
        Carbon $startedAt,
        Carbon $finishedAt
    ): int {
        return (int) round(
            $startedAt->diffInMilliseconds($finishedAt)
        );
    }
}