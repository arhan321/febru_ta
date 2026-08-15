<?php

namespace App\Console\Commands;

use Throwable;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReconcileInventoryDataWarehouse extends Command
{
    private const APPROVED_STATUS = 'approved';

    private const REQUIRED_TABLES = [
        'inbound_transactions',
        'inbound_transaction_items',
        'outbound_transactions',
        'outbound_transaction_items',
        'dw_fact_inbound_transactions',
        'dw_fact_outbound_transactions',
    ];

    protected $signature = 'dw:reconcile-inventory
                            {--limit=20 : Maksimum detail perbedaan yang ditampilkan}';

    protected $description = 'Memeriksa kesesuaian transaksi approved pada OLTP dengan tabel fakta Data Warehouse.';

    public function handle(): int
    {
        $missingTables = collect(self::REQUIRED_TABLES)
            ->reject(fn (string $table): bool => Schema::hasTable($table))
            ->values();

        if ($missingTables->isNotEmpty()) {
            $this->error('Rekonsiliasi tidak dapat dijalankan karena tabel berikut belum tersedia:');

            foreach ($missingTables as $table) {
                $this->line('- ' . $table);
            }

            return self::FAILURE;
        }

        $this->info('Memulai rekonsiliasi data operasional dan Data Warehouse...');
        $this->line('Hanya transaksi berstatus approved yang dihitung dari database operasional.');

        try {
            $results = [
                $this->reconcileTransactions(
                    label: 'Barang Masuk',
                    sourceTable: 'inbound_transactions',
                    itemTable: 'inbound_transaction_items',
                    itemForeignKey: 'inbound_transaction_id',
                    factTable: 'dw_fact_inbound_transactions',
                    factSourceColumn: 'source_inbound_id',
                ),
                $this->reconcileTransactions(
                    label: 'Barang Keluar',
                    sourceTable: 'outbound_transactions',
                    itemTable: 'outbound_transaction_items',
                    itemForeignKey: 'outbound_transaction_id',
                    factTable: 'dw_fact_outbound_transactions',
                    factSourceColumn: 'source_outbound_id',
                ),
            ];
        } catch (Throwable $exception) {
            $this->error('Rekonsiliasi gagal: ' . $exception->getMessage());

            report($exception);

            return self::FAILURE;
        }

        $this->newLine();
        $this->displayRecordSummary($results);

        $this->newLine();
        $this->displayAggregateSummary($results);

        foreach ($results as $result) {
            $this->displayDifferences($result);
        }

        $isSynchronized = collect($results)
            ->every(fn (array $result): bool => $result['is_synchronized']);

        $this->newLine();

        if ($isSynchronized) {
            $this->info('Rekonsiliasi selesai: data OLTP dan Data Warehouse SINKRON.');

            return self::SUCCESS;
        }

        $this->error('Rekonsiliasi selesai: ditemukan data yang TIDAK SINKRON.');
        $this->line('Jalankan php artisan dw:sync-inventory, lalu ulangi rekonsiliasi.');

        return self::FAILURE;
    }

    /**
     * @return array{
     *     label: string,
     *     source_count: int,
     *     fact_count: int,
     *     source_total_qty: float,
     *     fact_total_qty: float,
     *     source_total_value: float,
     *     fact_total_value: float,
     *     missing_ids: array<int, int>,
     *     extra_ids: array<int, int>,
     *     mismatches: array<int, array{
     *         source_id: int,
     *         field: string,
     *         source_value: mixed,
     *         fact_value: mixed,
     *         type: string
     *     }>,
     *     is_synchronized: bool
     * }
     */
    private function reconcileTransactions(
        string $label,
        string $sourceTable,
        string $itemTable,
        string $itemForeignKey,
        string $factTable,
        string $factSourceColumn,
    ): array {
        $sourceRows = $this->getApprovedSourceRows(
            sourceTable: $sourceTable,
            itemTable: $itemTable,
            itemForeignKey: $itemForeignKey,
        );

        $factRows = $this->getFactRows(
            factTable: $factTable,
            factSourceColumn: $factSourceColumn,
        );

        $sourceById = $this->indexRowsBySourceId($sourceRows);
        $factById = $this->indexRowsBySourceId($factRows);

        $sourceIds = array_keys($sourceById);
        $factIds = array_keys($factById);

        $missingIds = array_values(array_diff($sourceIds, $factIds));
        $extraIds = array_values(array_diff($factIds, $sourceIds));

        sort($missingIds);
        sort($extraIds);

        $mismatches = [];

        foreach (array_intersect($sourceIds, $factIds) as $sourceId) {
            $mismatches = array_merge(
                $mismatches,
                $this->compareTransactionRows(
                    sourceId: (int) $sourceId,
                    sourceRow: $sourceById[$sourceId],
                    factRow: $factById[$sourceId],
                )
            );
        }

        $sourceTotalQty = $this->sumColumn($sourceRows, 'total_qty');
        $factTotalQty = $this->sumColumn($factRows, 'total_qty');
        $sourceTotalValue = $this->sumColumn($sourceRows, 'grand_total');
        $factTotalValue = $this->sumColumn($factRows, 'grand_total');

        $isSynchronized = $sourceRows->count() === $factRows->count()
            && $missingIds === []
            && $extraIds === []
            && $mismatches === []
            && $this->decimalValuesMatch($sourceTotalQty, $factTotalQty)
            && $this->decimalValuesMatch($sourceTotalValue, $factTotalValue);

        return [
            'label' => $label,
            'source_count' => $sourceRows->count(),
            'fact_count' => $factRows->count(),
            'source_total_qty' => $sourceTotalQty,
            'fact_total_qty' => $factTotalQty,
            'source_total_value' => $sourceTotalValue,
            'fact_total_value' => $factTotalValue,
            'missing_ids' => $missingIds,
            'extra_ids' => $extraIds,
            'mismatches' => $mismatches,
            'is_synchronized' => $isSynchronized,
        ];
    }

    private function getApprovedSourceRows(
        string $sourceTable,
        string $itemTable,
        string $itemForeignKey,
    ): Collection {
        return DB::table($sourceTable . ' as trx')
            ->leftJoin(
                $itemTable . ' as item',
                'item.' . $itemForeignKey,
                '=',
                'trx.id'
            )
            ->where('trx.status', self::APPROVED_STATUS)
            ->groupBy(
                'trx.id',
                'trx.transaction_number',
                'trx.transaction_date',
                'trx.grand_total',
                'trx.status'
            )
            ->orderBy('trx.id')
            ->select([
                'trx.id as source_id',
                'trx.transaction_number',
                'trx.transaction_date',
                'trx.grand_total',
                'trx.status',
                DB::raw('COUNT(item.id) as total_items'),
                DB::raw('COALESCE(SUM(item.qty), 0) as total_qty'),
            ])
            ->get()
            ->map(function (object $row): object {
                $row->date_key = $this->dateKey($row->transaction_date);

                return $row;
            });
    }

    private function getFactRows(
        string $factTable,
        string $factSourceColumn,
    ): Collection {
        return DB::table($factTable)
            ->orderBy($factSourceColumn)
            ->get([
                $factSourceColumn . ' as source_id',
                'transaction_number',
                'date_key',
                'total_items',
                'total_qty',
                'grand_total',
                'status',
            ]);
    }

    /**
     * @return array<int, object>
     */
    private function indexRowsBySourceId(Collection $rows): array
    {
        $indexedRows = [];

        foreach ($rows as $row) {
            $indexedRows[(int) $row->source_id] = $row;
        }

        return $indexedRows;
    }

    /**
     * @return array<int, array{
     *     source_id: int,
     *     field: string,
     *     source_value: mixed,
     *     fact_value: mixed,
     *     type: string
     * }>
     */
    private function compareTransactionRows(
        int $sourceId,
        object $sourceRow,
        object $factRow,
    ): array {
        $fields = [
            'transaction_number' => 'string',
            'date_key' => 'integer',
            'total_items' => 'integer',
            'total_qty' => 'decimal',
            'grand_total' => 'money',
            'status' => 'string',
        ];

        $differences = [];

        foreach ($fields as $field => $type) {
            $sourceValue = $sourceRow->{$field} ?? null;
            $factValue = $factRow->{$field} ?? null;

            if ($this->valuesMatch($sourceValue, $factValue, $type)) {
                continue;
            }

            $differences[] = [
                'source_id' => $sourceId,
                'field' => $field,
                'source_value' => $sourceValue,
                'fact_value' => $factValue,
                'type' => $type,
            ];
        }

        return $differences;
    }

    private function valuesMatch(mixed $sourceValue, mixed $factValue, string $type): bool
    {
        return match ($type) {
            'integer' => (int) $sourceValue === (int) $factValue,
            'decimal', 'money' => $this->decimalValuesMatch(
                (float) $sourceValue,
                (float) $factValue
            ),
            default => (string) $sourceValue === (string) $factValue,
        };
    }

    private function decimalValuesMatch(float $sourceValue, float $factValue): bool
    {
        return abs($sourceValue - $factValue) < 0.005;
    }

    private function sumColumn(Collection $rows, string $column): float
    {
        return (float) $rows->sum(
            fn (object $row): float => (float) ($row->{$column} ?? 0)
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    private function displayRecordSummary(array $results): void
    {
        $this->table(
            [
                'Jenis',
                'OLTP Approved',
                'Fakta DW',
                'Hilang',
                'Berlebih',
                'Tidak Sesuai',
                'Status',
            ],
            array_map(
                fn (array $result): array => [
                    $result['label'],
                    $result['source_count'],
                    $result['fact_count'],
                    count($result['missing_ids']),
                    count($result['extra_ids']),
                    count($result['mismatches']),
                    $result['is_synchronized'] ? 'SINKRON' : 'TIDAK SINKRON',
                ],
                $results
            )
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    private function displayAggregateSummary(array $results): void
    {
        $this->table(
            [
                'Jenis',
                'Qty OLTP',
                'Qty DW',
                'Nilai OLTP',
                'Nilai DW',
            ],
            array_map(
                fn (array $result): array => [
                    $result['label'],
                    $this->formatDecimal($result['source_total_qty']),
                    $this->formatDecimal($result['fact_total_qty']),
                    $this->formatMoney($result['source_total_value']),
                    $this->formatMoney($result['fact_total_value']),
                ],
                $results
            )
        );
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function displayDifferences(array $result): void
    {
        if ($result['is_synchronized']) {
            return;
        }

        $limit = max(1, (int) $this->option('limit'));

        $this->newLine();
        $this->warn('Detail perbedaan ' . $result['label'] . ':');

        if ($result['missing_ids'] !== []) {
            $this->line(
                'ID belum masuk DW: '
                . $this->formatIdList($result['missing_ids'], $limit)
            );
        }

        if ($result['extra_ids'] !== []) {
            $this->line(
                'ID berlebih di DW: '
                . $this->formatIdList($result['extra_ids'], $limit)
            );
        }

        if ($result['mismatches'] === []) {
            return;
        }

        $visibleMismatches = array_slice($result['mismatches'], 0, $limit);

        $this->table(
            ['ID Sumber', 'Kolom', 'Nilai OLTP', 'Nilai DW'],
            array_map(
                fn (array $mismatch): array => [
                    $mismatch['source_id'],
                    $this->fieldLabel($mismatch['field']),
                    $this->formatFieldValue(
                        $mismatch['source_value'],
                        $mismatch['type']
                    ),
                    $this->formatFieldValue(
                        $mismatch['fact_value'],
                        $mismatch['type']
                    ),
                ],
                $visibleMismatches
            )
        );

        $hiddenCount = count($result['mismatches']) - count($visibleMismatches);

        if ($hiddenCount > 0) {
            $this->line('... dan ' . $hiddenCount . ' perbedaan lainnya.');
        }
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function formatIdList(array $ids, int $limit): string
    {
        $visibleIds = array_slice($ids, 0, $limit);
        $text = implode(', ', $visibleIds);
        $hiddenCount = count($ids) - count($visibleIds);

        if ($hiddenCount > 0) {
            $text .= ' (+' . $hiddenCount . ' lainnya)';
        }

        return $text;
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'transaction_number' => 'Nomor Transaksi',
            'date_key' => 'Tanggal',
            'total_items' => 'Jumlah Item',
            'total_qty' => 'Total Qty',
            'grand_total' => 'Grand Total',
            'status' => 'Status',
            default => $field,
        };
    }

    private function formatFieldValue(mixed $value, string $type): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return match ($type) {
            'money' => $this->formatMoney((float) $value),
            'decimal' => $this->formatDecimal((float) $value),
            'integer' => (string) (int) $value,
            default => (string) $value,
        };
    }

    private function formatDecimal(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }

    private function formatMoney(float $value): string
    {
        return 'Rp ' . number_format($value, 2, ',', '.');
    }

    private function dateKey(mixed $date): int
    {
        return (int) Carbon::parse($date)->format('Ymd');
    }
}