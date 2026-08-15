<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    expect(
        \App\Console\Commands\SyncInventoryDataWarehouse::IMPLEMENTATION_VERSION
    )->toBe('ETL-FIX-2026-07-28-V3');
});

it('hanya memuat transaksi approved dan menghapus fakta yang tidak lagi approved', function () {
    $now = now();
    $warehouseId = createWarehouseForDataWarehouseTest('FILTER');
    $productId = createProductForDataWarehouseTest('FILTER');

    $inboundIds = [];
    $outboundIds = [];

    foreach (['approved', 'pending', 'rejected'] as $index => $status) {
        $sequence = $index + 1;

        $inboundIds[$status] = createInboundTransactionForDataWarehouseTest(
            warehouseId: $warehouseId,
            productId: $productId,
            number: "IN-FILTER-{$sequence}",
            status: $status,
            qty: 10,
            grandTotal: 100000,
        );

        $outboundIds[$status] = createOutboundTransactionForDataWarehouseTest(
            warehouseId: $warehouseId,
            productId: $productId,
            number: "OUT-FILTER-{$sequence}",
            status: $status,
            qty: 5,
            grandTotal: 75000,
        );
    }

    runDataWarehouseCommand('dw:sync-inventory');

    expect(
        DB::table('dw_fact_inbound_transactions')
            ->orderBy('source_inbound_id')
            ->pluck('source_inbound_id')
            ->all()
    )
        ->toBe([$inboundIds['approved']])
        ->and(
            DB::table('dw_fact_outbound_transactions')
                ->orderBy('source_outbound_id')
                ->pluck('source_outbound_id')
                ->all()
        )
        ->toBe([$outboundIds['approved']])
        ->and(
            DB::table('dw_fact_inventory_movements')
                ->where('reference_type', 'inbound_transaction')
                ->pluck('reference_id')
                ->all()
        )
        ->toBe([$inboundIds['approved']])
        ->and(
            DB::table('dw_fact_inventory_movements')
                ->where('reference_type', 'outbound_transaction')
                ->pluck('reference_id')
                ->all()
        )
        ->toBe([$outboundIds['approved']]);

    DB::table('inbound_transactions')
        ->where('id', $inboundIds['approved'])
        ->update([
            'status' => 'rejected',
            'updated_at' => $now,
        ]);

    DB::table('inbound_transactions')
        ->where('id', $inboundIds['pending'])
        ->update([
            'status' => 'approved',
            'approved_at' => $now,
            'updated_at' => $now,
        ]);

    DB::table('outbound_transactions')
        ->where('id', $outboundIds['approved'])
        ->update([
            'status' => 'rejected',
            'updated_at' => $now,
        ]);

    DB::table('outbound_transactions')
        ->where('id', $outboundIds['pending'])
        ->update([
            'status' => 'approved',
            'approved_at' => $now,
            'updated_at' => $now,
        ]);

    runDataWarehouseCommand('dw:sync-inventory');

    expect(
        DB::table('dw_fact_inbound_transactions')
            ->pluck('source_inbound_id')
            ->all()
    )
        ->toBe([$inboundIds['pending']])
        ->and(
            DB::table('dw_fact_outbound_transactions')
                ->pluck('source_outbound_id')
                ->all()
        )
        ->toBe([$outboundIds['pending']]);

    $this->assertDatabaseMissing('dw_fact_inbound_transactions', [
        'source_inbound_id' => $inboundIds['approved'],
    ]);

    $this->assertDatabaseMissing('dw_fact_outbound_transactions', [
        'source_outbound_id' => $outboundIds['approved'],
    ]);
});

it('mendeteksi data sinkron dan perbedaan antara OLTP dengan Data Warehouse', function () {
    $warehouseId = createWarehouseForDataWarehouseTest('RECON');
    $productId = createProductForDataWarehouseTest('RECON');

    $inboundId = createInboundTransactionForDataWarehouseTest(
        warehouseId: $warehouseId,
        productId: $productId,
        number: 'IN-RECON-1',
        status: 'approved',
        qty: 12,
        grandTotal: 144000,
    );

    createOutboundTransactionForDataWarehouseTest(
        warehouseId: $warehouseId,
        productId: $productId,
        number: 'OUT-RECON-1',
        status: 'approved',
        qty: 4,
        grandTotal: 68000,
    );

    runDataWarehouseCommand('dw:sync-inventory');

    $this->artisan('dw:reconcile-inventory')
        ->expectsOutputToContain(
            'Rekonsiliasi selesai: data OLTP dan Data Warehouse SINKRON.'
        )
        ->assertSuccessful();

    DB::table('dw_fact_inbound_transactions')
        ->where('source_inbound_id', $inboundId)
        ->update(['total_qty' => 999]);

    $this->artisan('dw:reconcile-inventory')
        ->expectsOutputToContain(
            'Rekonsiliasi selesai: ditemukan data yang TIDAK SINKRON.'
        )
        ->assertFailed();
});

it('tetap idempoten saat sinkronisasi dan rekonsiliasi dijalankan berulang', function () {
    $warehouseId = createWarehouseForDataWarehouseTest('IDEMPOTENT');
    $productId = createProductForDataWarehouseTest('IDEMPOTENT');

    $inboundId = createInboundTransactionForDataWarehouseTest(
        warehouseId: $warehouseId,
        productId: $productId,
        number: 'IN-IDEMPOTENT-1',
        status: 'approved',
        qty: 20,
        grandTotal: 240000,
    );

    $outboundId = createOutboundTransactionForDataWarehouseTest(
        warehouseId: $warehouseId,
        productId: $productId,
        number: 'OUT-IDEMPOTENT-1',
        status: 'approved',
        qty: 7,
        grandTotal: 119000,
    );

    runDataWarehouseCommand('dw:sync-and-reconcile');

    $firstCounts = inventoryDataWarehouseFactCounts();

    runDataWarehouseCommand('dw:sync-and-reconcile');

    $secondCounts = inventoryDataWarehouseFactCounts();
    $runs = DB::table('dw_etl_runs')->orderBy('id')->get();

    expect($secondCounts)
        ->toBe($firstCounts)
        ->and($secondCounts['dw_fact_inbound_transactions'])
        ->toBe(1)
        ->and($secondCounts['dw_fact_outbound_transactions'])
        ->toBe(1)
        ->and($secondCounts['dw_fact_inventory_movements'])
        ->toBe(2)
        ->and(
            DB::table('dw_fact_inbound_transactions')
                ->where('source_inbound_id', $inboundId)
                ->count()
        )
        ->toBe(1)
        ->and(
            DB::table('dw_fact_outbound_transactions')
                ->where('source_outbound_id', $outboundId)
                ->count()
        )
        ->toBe(1)
        ->and($runs)
        ->toHaveCount(2)
        ->and($runs->pluck('run_uuid')->unique())
        ->toHaveCount(2)
        ->and($runs->pluck('status')->unique()->all())
        ->toBe(['success'])
        ->and($runs->pluck('reconciliation_status')->unique()->all())
        ->toBe(['success']);

    foreach ($runs as $run) {
        expect($run->finished_at)
            ->not->toBeNull()
            ->and($run->reconciled_at)
            ->not->toBeNull()
            ->and($run->reconciliation_message)
            ->toBe(
                'Data OLTP approved dan Data Warehouse telah dinyatakan sinkron.'
            );
    }
});

function createWarehouseForDataWarehouseTest(string $suffix): int
{
    return DB::table('warehouses')->insertGetId([
        'code' => "WH-{$suffix}",
        'name' => "Gudang {$suffix}",
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createProductForDataWarehouseTest(string $suffix): int
{
    return DB::table('products')->insertGetId([
        'code' => "PRD-{$suffix}",
        'name' => "Produk {$suffix}",
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createInboundTransactionForDataWarehouseTest(
    int $warehouseId,
    int $productId,
    string $number,
    string $status,
    float $qty,
    float $grandTotal,
): int {
    $now = now();

    $transactionId = DB::table('inbound_transactions')->insertGetId([
        'transaction_number' => $number,
        'transaction_date' => '2026-07-26',
        'warehouse_id' => $warehouseId,
        'status' => $status,
        'sub_total' => $grandTotal,
        'grand_total' => $grandTotal,
        'approved_at' => $status === 'approved' ? $now : null,
        'source' => 'mobile',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('inbound_transaction_items')->insert([
        'inbound_transaction_id' => $transactionId,
        'product_id' => $productId,
        'warehouse_id' => $warehouseId,
        'qty' => $qty,
        'unit_cost' => $qty > 0 ? $grandTotal / $qty : 0,
        'subtotal' => $grandTotal,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return $transactionId;
}

function createOutboundTransactionForDataWarehouseTest(
    int $warehouseId,
    int $productId,
    string $number,
    string $status,
    float $qty,
    float $grandTotal,
): int {
    $now = now();

    $transactionId = DB::table('outbound_transactions')->insertGetId([
        'transaction_number' => $number,
        'transaction_date' => '2026-07-26',
        'warehouse_id' => $warehouseId,
        'status' => $status,
        'sub_total' => $grandTotal,
        'grand_total' => $grandTotal,
        'paid_amount' => $grandTotal,
        'remaining_amount' => 0,
        'approved_at' => $status === 'approved' ? $now : null,
        'source' => 'mobile',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('outbound_transaction_items')->insert([
        'outbound_transaction_id' => $transactionId,
        'product_id' => $productId,
        'warehouse_id' => $warehouseId,
        'qty' => $qty,
        'unit_price' => $qty > 0 ? $grandTotal / $qty : 0,
        'subtotal' => $grandTotal,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return $transactionId;
}

/**
 * @return array<string, int>
 */
function inventoryDataWarehouseFactCounts(): array
{
    return collect([
        'dw_fact_inventory_movements',
        'dw_fact_inbound_transactions',
        'dw_fact_outbound_transactions',
        'dw_fact_stock_snapshots',
        'dw_fact_asset_snapshots',
    ])->mapWithKeys(
        fn (string $table): array => [$table => DB::table($table)->count()]
    )->all();
}

function runDataWarehouseCommand(string $command): void
{
    $exitCode = Artisan::call($command);
    $output = trim(Artisan::output());
    $etlError = null;

    if (DB::getSchemaBuilder()->hasTable('dw_etl_runs')) {
        $etlError = DB::table('dw_etl_runs')
            ->latest('id')
            ->value('error_message');
    }

    $diagnostic = collect([
        $output !== '' ? $output : null,
        $etlError
            ? "Error terakhir pada dw_etl_runs:\n{$etlError}"
            : null,
    ])->filter()->implode("\n\n");

    expect($exitCode)->toBe(
        Command::SUCCESS,
        $diagnostic !== ''
            ? "Command [{$command}] gagal.\n\n{$diagnostic}"
            : "Command [{$command}] gagal tanpa menghasilkan output."
    );
}
