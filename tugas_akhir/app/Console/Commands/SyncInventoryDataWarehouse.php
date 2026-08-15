<?php

namespace App\Console\Commands;

use Throwable;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Asset;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use App\Models\StockBalance;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use App\Models\InboundTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\OutboundTransaction;
use Illuminate\Support\Facades\Schema;

class SyncInventoryDataWarehouse extends Command
{
    public const IMPLEMENTATION_VERSION = 'ETL-FIX-2026-07-28-V3';

    private const APPROVED_TRANSACTION_STATUS = 'approved';

    private const DIMENSION_TABLES = [
        'dw_dim_dates',
        'dw_dim_products',
        'dw_dim_warehouses',
        'dw_dim_suppliers',
        'dw_dim_customers',
        'dw_dim_users',
        'dw_dim_asset_categories',
        'dw_dim_asset_locations',
        'dw_dim_assets',
    ];

    private const FACT_TABLES = [
        'dw_fact_inventory_movements',
        'dw_fact_inbound_transactions',
        'dw_fact_outbound_transactions',
        'dw_fact_stock_snapshots',
        'dw_fact_asset_snapshots',
    ];

    protected $signature = 'dw:sync-inventory';

    protected $description = 'Sync operational inventory data into data warehouse tables.';

    public function handle(): int
    {
        $this->info('Starting inventory data warehouse sync...');

        $startedAt = now();
        $etlRunId = $this->startEtlRun($startedAt);

        try {
            DB::transaction(function (): void {
                $this->syncDimensions();
                $this->syncFacts();
            });

            $this->completeEtlRun($etlRunId, $startedAt);

            $this->info('Inventory data warehouse sync completed successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->failEtlRun($etlRunId, $startedAt, $e);

            $this->error('Sync failed: ' . $e->getMessage());

            report($e);

            /*
             * During automated tests, expose the original exception and stack
             * trace instead of reducing every failure to console exit code 1.
             * Non-testing environments keep the normal, controlled CLI failure.
             */
            if (app()->runningUnitTests()) {
                throw $e;
            }

            return self::FAILURE;
        }
    }

    private function syncDimensions(): void
    {
        $this->info('Syncing dimensions...');

        $this->syncDimProducts();
        $this->syncDimWarehouses();
        $this->syncDimSuppliers();
        $this->syncDimCustomers();
        $this->syncDimUsers();
        $this->syncDimAssetCategories();
        $this->syncDimAssetLocations();
        $this->syncDimAssets();
    }

    private function syncFacts(): void
    {
        $this->info('Syncing facts...');

        $this->syncFactInboundTransactions();
        $this->syncFactOutboundTransactions();
        $this->syncFactInventoryMovements();
        $this->syncFactStockSnapshots();
        $this->syncFactAssetSnapshots();
    }

    private function syncDimProducts(): void
    {
        Product::query()
            ->with(['productType', 'productDensity', 'productCategory', 'unit'])
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    DB::table('dw_dim_products')->updateOrInsert(
                        ['source_product_id' => $product->id],
                        $this->filterColumns('dw_dim_products', [
                            'code' => $product->code,
                            'name' => $product->name,
                            'full_name' => $product->full_name,
                            'type_name' => $product->productType?->name,
                            'density_name' => $product->productDensity?->name,
                            'category_name' => $product->productCategory?->name,
                            'unit_name' => $product->unit?->name,
                            'default_purchase_price' => (float) ($product->default_purchase_price ?? 0),
                            'default_selling_price' => (float) ($product->default_selling_price ?? 0),
                            'is_active' => (bool) ($product->is_active ?? true),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            });
    }

    private function syncDimWarehouses(): void
    {
        Warehouse::query()
            ->chunkById(200, function ($warehouses): void {
                foreach ($warehouses as $warehouse) {
                    DB::table('dw_dim_warehouses')->updateOrInsert(
                        ['source_warehouse_id' => $warehouse->id],
                        $this->filterColumns('dw_dim_warehouses', [
                            'code' => $warehouse->code,
                            'name' => $warehouse->name,
                            'address' => $warehouse->address,
                            'phone' => $warehouse->phone,
                            'is_active' => (bool) ($warehouse->is_active ?? true),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            });
    }

    private function syncDimSuppliers(): void
    {
        Supplier::query()
            ->chunkById(200, function ($suppliers): void {
                foreach ($suppliers as $supplier) {
                    DB::table('dw_dim_suppliers')->updateOrInsert(
                        ['source_supplier_id' => $supplier->id],
                        $this->filterColumns('dw_dim_suppliers', [
                            'code' => $supplier->code,
                            'name' => $supplier->name,
                            'phone' => $supplier->phone,
                            'address' => $supplier->address,
                            'is_active' => (bool) ($supplier->is_active ?? true),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            });
    }

    private function syncDimCustomers(): void
    {
        Customer::query()
            ->chunkById(200, function ($customers): void {
                foreach ($customers as $customer) {
                    DB::table('dw_dim_customers')->updateOrInsert(
                        ['source_customer_id' => $customer->id],
                        $this->filterColumns('dw_dim_customers', [
                            'code' => $customer->code,
                            'name' => $customer->name,
                            'phone' => $customer->phone,
                            'address' => $customer->address,
                            'customer_type' => $customer->customer_type,
                            'is_active' => (bool) ($customer->is_active ?? true),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            });
    }

    private function syncDimUsers(): void
    {
        User::query()
            ->with(['profile.warehouse'])
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    DB::table('dw_dim_users')->updateOrInsert(
                        ['source_user_id' => $user->id],
                        $this->filterColumns('dw_dim_users', [
                            'name' => $user->name,
                            'email' => $user->email,
                            'username' => $user->profile?->username,
                            'employee_code' => $user->profile?->employee_code,
                            'position' => $user->profile?->position,
                            'warehouse_name' => $user->profile?->warehouse?->name,
                            'is_active' => (bool) ($user->profile?->is_active ?? true),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            });
    }

    private function syncFactInventoryMovements(): void
    {
        /*
         * Jangan hanya membaca stock_movements.
         * Data histori lama dengan Update Stok Operasional OFF tidak membuat stock_movements,
         * tetapi tetap tersimpan di inbound/outbound transaction items dan harus masuk chart DW.
         */
        if (! Schema::hasTable('dw_fact_inventory_movements')) {
            return;
        }

        DB::table('dw_fact_inventory_movements')->delete();

        $this->syncInboundTransactionItemsAsInventoryMovements();
        $this->syncOutboundTransactionItemsAsInventoryMovements();
        $this->syncOtherStockMovementsAsInventoryMovements();
    }

    private function syncInboundTransactionItemsAsInventoryMovements(): void
    {
        if (! Schema::hasTable('inbound_transactions') || ! Schema::hasTable('inbound_transaction_items')) {
            return;
        }

        $warehouseExpression = $this->warehouseExpression(
            'inbound_transaction_items',
            'inbound_transactions',
            'item',
            'trx'
        );

        if (! $warehouseExpression) {
            return;
        }

        $select = [
            'item.id as item_id',
            'item.inbound_transaction_id as transaction_id',
            'item.product_id',
            DB::raw($warehouseExpression . ' as warehouse_id'),
            'item.qty',
            'item.created_at as item_created_at',
            'trx.transaction_number',
            'trx.transaction_date',
            'trx.created_at as transaction_created_at',
        ];

        if (Schema::hasColumn('inbound_transactions', 'invoice_number')) {
            $select[] = 'trx.invoice_number';
        } else {
            $select[] = DB::raw('NULL as invoice_number');
        }

        if (Schema::hasColumn('inbound_transactions', 'submitted_by')) {
            $select[] = 'trx.submitted_by';
        } else {
            $select[] = DB::raw('NULL as submitted_by');
        }

        if (Schema::hasColumn('inbound_transactions', 'approved_by')) {
            $select[] = 'trx.approved_by';
        } else {
            $select[] = DB::raw('NULL as approved_by');
        }

        $query = DB::table('inbound_transaction_items as item')
            ->join('inbound_transactions as trx', 'trx.id', '=', 'item.inbound_transaction_id')
            ->whereNotNull('item.product_id')
            ->where('item.qty', '>', 0)
            ->whereRaw($warehouseExpression . ' IS NOT NULL')
            ->orderBy('trx.transaction_date')
            ->orderBy('trx.id')
            ->orderBy('item.id')
            ->select($select);

        $this->applyApprovedTransactionFilter($query, 'inbound_transactions', 'trx');

        foreach ($query->get() as $row) {
            $date = $this->dateFromTransaction(
                $row->transaction_date,
                $row->transaction_created_at,
                $row->item_created_at
            );

            $dateKey = $this->ensureDateDimension($date);
            $productDimId = $this->getProductDimId((int) $row->product_id);
            $warehouseDimId = $this->getWarehouseDimId((int) $row->warehouse_id);

            $userDimId = $row->approved_by
                ? $this->getUserDimId((int) $row->approved_by)
                : ($row->submitted_by ? $this->getUserDimId((int) $row->submitted_by) : null);

            if (! $productDimId || ! $warehouseDimId) {
                continue;
            }

            $qty = (float) $row->qty;
            $movementNumber = 'DW-IN-ITEM-' . $row->item_id;
            $sourceStockMovementId = $this->syntheticSourceStockMovementId('in', (int) $row->item_id);

            $this->upsertInventoryMovement(
                $this->inventoryMovementUniqueKey($movementNumber, $sourceStockMovementId),
                [
                    'source_stock_movement_id' => $sourceStockMovementId,
                    'movement_number' => $movementNumber,
                    'movement_type' => 'in',
                    'date_key' => $dateKey,
                    'product_dim_id' => $productDimId,
                    'warehouse_dim_id' => $warehouseDimId,
                    'user_dim_id' => $userDimId,
                    'qty_in' => $qty,
                    'qty_out' => 0,
                    'stock_before' => 0,
                    'stock_after' => 0,
                    'reference_type' => 'inbound_transaction',
                    'reference_id' => (int) $row->transaction_id,
                    'description' => 'ETL barang masuk dari transaksi ' . ($row->transaction_number ?? $row->invoice_number ?? '-'),
                    'movement_created_at' => $date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function syncOutboundTransactionItemsAsInventoryMovements(): void
    {
        if (! Schema::hasTable('outbound_transactions') || ! Schema::hasTable('outbound_transaction_items')) {
            return;
        }

        $warehouseExpression = $this->warehouseExpression(
            'outbound_transaction_items',
            'outbound_transactions',
            'item',
            'trx'
        );

        if (! $warehouseExpression) {
            return;
        }

        $select = [
            'item.id as item_id',
            'item.outbound_transaction_id as transaction_id',
            'item.product_id',
            DB::raw($warehouseExpression . ' as warehouse_id'),
            'item.qty',
            'item.created_at as item_created_at',
            'trx.transaction_number',
            'trx.transaction_date',
            'trx.created_at as transaction_created_at',
        ];

        if (Schema::hasColumn('outbound_transaction_items', 'stock_before_submit')) {
            $select[] = 'item.stock_before_submit';
        } else {
            $select[] = DB::raw('0 as stock_before_submit');
        }

        if (Schema::hasColumn('outbound_transaction_items', 'stock_after_submit')) {
            $select[] = 'item.stock_after_submit';
        } else {
            $select[] = DB::raw('0 as stock_after_submit');
        }

        if (Schema::hasColumn('outbound_transactions', 'reference_number')) {
            $select[] = 'trx.reference_number';
        } else {
            $select[] = DB::raw('NULL as reference_number');
        }

        if (Schema::hasColumn('outbound_transactions', 'submitted_by')) {
            $select[] = 'trx.submitted_by';
        } else {
            $select[] = DB::raw('NULL as submitted_by');
        }

        if (Schema::hasColumn('outbound_transactions', 'approved_by')) {
            $select[] = 'trx.approved_by';
        } else {
            $select[] = DB::raw('NULL as approved_by');
        }

        $query = DB::table('outbound_transaction_items as item')
            ->join('outbound_transactions as trx', 'trx.id', '=', 'item.outbound_transaction_id')
            ->whereNotNull('item.product_id')
            ->where('item.qty', '>', 0)
            ->whereRaw($warehouseExpression . ' IS NOT NULL')
            ->orderBy('trx.transaction_date')
            ->orderBy('trx.id')
            ->orderBy('item.id')
            ->select($select);

        $this->applyApprovedTransactionFilter($query, 'outbound_transactions', 'trx');

        foreach ($query->get() as $row) {
            $date = $this->dateFromTransaction(
                $row->transaction_date,
                $row->transaction_created_at,
                $row->item_created_at
            );

            $dateKey = $this->ensureDateDimension($date);
            $productDimId = $this->getProductDimId((int) $row->product_id);
            $warehouseDimId = $this->getWarehouseDimId((int) $row->warehouse_id);

            $userDimId = $row->approved_by
                ? $this->getUserDimId((int) $row->approved_by)
                : ($row->submitted_by ? $this->getUserDimId((int) $row->submitted_by) : null);

            if (! $productDimId || ! $warehouseDimId) {
                continue;
            }

            $qty = (float) $row->qty;
            $movementNumber = 'DW-OUT-ITEM-' . $row->item_id;
            $sourceStockMovementId = $this->syntheticSourceStockMovementId('out', (int) $row->item_id);

            $this->upsertInventoryMovement(
                $this->inventoryMovementUniqueKey($movementNumber, $sourceStockMovementId),
                [
                    'source_stock_movement_id' => $sourceStockMovementId,
                    'movement_number' => $movementNumber,
                    'movement_type' => 'out',
                    'date_key' => $dateKey,
                    'product_dim_id' => $productDimId,
                    'warehouse_dim_id' => $warehouseDimId,
                    'user_dim_id' => $userDimId,
                    'qty_in' => 0,
                    'qty_out' => $qty,
                    'stock_before' => (float) ($row->stock_before_submit ?? 0),
                    'stock_after' => (float) ($row->stock_after_submit ?? 0),
                    'reference_type' => 'outbound_transaction',
                    'reference_id' => (int) $row->transaction_id,
                    'description' => 'ETL barang keluar dari transaksi ' . ($row->transaction_number ?? $row->reference_number ?? '-'),
                    'movement_created_at' => $date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function syncOtherStockMovementsAsInventoryMovements(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        StockMovement::query()
            ->where(function ($query): void {
                $query->whereNull('reference_type')
                    ->orWhereNotIn('reference_type', [
                        'inbound_transaction',
                        'outbound_transaction',
                        InboundTransaction::class,
                        OutboundTransaction::class,
                    ]);
            })
            ->chunkById(200, function ($movements): void {
                foreach ($movements as $movement) {
                    $date = $movement->created_at ?? now();
                    $dateKey = $this->ensureDateDimension($date);

                    $productDimId = $this->getProductDimId((int) $movement->product_id);
                    $warehouseDimId = $this->getWarehouseDimId((int) $movement->warehouse_id);

                    if (! $productDimId || ! $warehouseDimId) {
                        continue;
                    }

                    $movementType = strtolower((string) $movement->movement_type);

                    $qty = (float) (
                        $movement->qty
                        ?? $movement->quantity
                        ?? $movement->qty_in
                        ?? $movement->qty_out
                        ?? 0
                    );

                    $qtyIn = (float) ($movement->qty_in ?? 0);
                    $qtyOut = (float) ($movement->qty_out ?? 0);

                    if ($qtyIn <= 0 && $qtyOut <= 0) {
                        if (in_array($movementType, ['in', 'masuk', 'inbound'], true)) {
                            $qtyIn = $qty;
                        }

                        if (in_array($movementType, ['out', 'keluar', 'outbound'], true)) {
                            $qtyOut = $qty;
                        }
                    }

                    $userId = $movement->created_by
                        ?? $movement->user_id
                        ?? $movement->submitted_by
                        ?? null;

                    $userDimId = $userId ? $this->getUserDimId((int) $userId) : null;
                    $movementNumber = $movement->movement_number ?: 'DW-STOCK-MOVEMENT-' . $movement->id;

                    $this->upsertInventoryMovement(
                        Schema::hasColumn('dw_fact_inventory_movements', 'source_stock_movement_id')
                            ? ['source_stock_movement_id' => $movement->id]
                            : $this->inventoryMovementUniqueKey($movementNumber, (int) $movement->id),
                        [
                            'source_stock_movement_id' => $movement->id,
                            'movement_number' => $movementNumber,
                            'movement_type' => $movement->movement_type,
                            'date_key' => $dateKey,
                            'product_dim_id' => $productDimId,
                            'warehouse_dim_id' => $warehouseDimId,
                            'user_dim_id' => $userDimId,
                            'qty_in' => $qtyIn,
                            'qty_out' => $qtyOut,
                            'stock_before' => (float) ($movement->stock_before ?? 0),
                            'stock_after' => (float) ($movement->stock_after ?? 0),
                            'reference_type' => $movement->reference_type ?? null,
                            'reference_id' => $movement->reference_id ?? null,
                            'description' => $movement->description ?? $movement->note ?? null,
                            'movement_created_at' => $movement->created_at,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            });
    }

    private function syncFactInboundTransactions(): void
    {
        $this->deleteUnapprovedTransactionFacts(
            'dw_fact_inbound_transactions',
            'source_inbound_id',
            'inbound_transactions'
        );

        InboundTransaction::query()
            ->where('status', self::APPROVED_TRANSACTION_STATUS)
            ->with(['items'])
            ->chunkById(100, function ($transactions): void {
                foreach ($transactions as $transaction) {
                    $date = $transaction->transaction_date ?? $transaction->created_at ?? now();
                    $dateKey = $this->ensureDateDimension($date);

                    $warehouseDimId = $this->getWarehouseDimId((int) $transaction->warehouse_id);

                    $supplierDimId = $transaction->supplier_id
                        ? $this->getSupplierDimId((int) $transaction->supplier_id)
                        : null;

                    $submittedUserDimId = $transaction->submitted_by
                        ? $this->getUserDimId((int) $transaction->submitted_by)
                        : null;

                    $approvedUserDimId = $transaction->approved_by
                        ? $this->getUserDimId((int) $transaction->approved_by)
                        : null;

                    if (! $warehouseDimId) {
                        continue;
                    }

                    DB::table('dw_fact_inbound_transactions')->updateOrInsert(
                        ['source_inbound_id' => $transaction->id],
                        $this->filterColumns('dw_fact_inbound_transactions', [
                            'transaction_number' => $transaction->transaction_number,
                            'invoice_number' => $transaction->invoice_number,
                            'date_key' => $dateKey,
                            'warehouse_dim_id' => $warehouseDimId,
                            'supplier_dim_id' => $supplierDimId,
                            'submitted_user_dim_id' => $submittedUserDimId,
                            'approved_user_dim_id' => $approvedUserDimId,
                            'total_items' => $transaction->items->count(),
                            'total_qty' => (float) $transaction->items->sum('qty'),
                            'sub_total' => (float) ($transaction->sub_total ?? 0),
                            'discount_amount' => (float) ($transaction->discount_amount ?? 0),
                            'other_cost' => (float) ($transaction->other_cost ?? 0),
                            'grand_total' => (float) ($transaction->grand_total ?? 0),
                            'status' => $transaction->status,
                            'source' => $transaction->source,
                            'submitted_at' => $transaction->submitted_at,
                            'approved_at' => $transaction->approved_at,
                            'transaction_created_at' => $transaction->created_at,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            });
    }

    private function syncFactOutboundTransactions(): void
    {
        $this->deleteUnapprovedTransactionFacts(
            'dw_fact_outbound_transactions',
            'source_outbound_id',
            'outbound_transactions'
        );

        OutboundTransaction::query()
            ->where('status', self::APPROVED_TRANSACTION_STATUS)
            ->with(['items'])
            ->chunkById(100, function ($transactions): void {
                foreach ($transactions as $transaction) {
                    $date = $transaction->transaction_date ?? $transaction->created_at ?? now();
                    $dateKey = $this->ensureDateDimension($date);

                    $warehouseDimId = $this->getWarehouseDimId((int) $transaction->warehouse_id);

                    $customerDimId = $transaction->customer_id
                        ? $this->getCustomerDimId((int) $transaction->customer_id)
                        : null;

                    $submittedUserDimId = $transaction->submitted_by
                        ? $this->getUserDimId((int) $transaction->submitted_by)
                        : null;

                    $approvedUserDimId = $transaction->approved_by
                        ? $this->getUserDimId((int) $transaction->approved_by)
                        : null;

                    if (! $warehouseDimId) {
                        continue;
                    }

                    DB::table('dw_fact_outbound_transactions')->updateOrInsert(
                        ['source_outbound_id' => $transaction->id],
                        $this->filterColumns('dw_fact_outbound_transactions', [
                            'transaction_number' => $transaction->transaction_number,
                            'reference_number' => $transaction->reference_number,
                            'outbound_type' => $transaction->outbound_type,
                            'date_key' => $dateKey,
                            'warehouse_dim_id' => $warehouseDimId,
                            'customer_dim_id' => $customerDimId,
                            'submitted_user_dim_id' => $submittedUserDimId,
                            'approved_user_dim_id' => $approvedUserDimId,
                            'total_items' => $transaction->items->count(),
                            'total_qty' => (float) $transaction->items->sum('qty'),
                            'sub_total' => (float) ($transaction->sub_total ?? 0),
                            'discount_amount' => (float) ($transaction->discount_amount ?? 0),
                            'vat_amount' => (float) ($transaction->vat_amount ?? 0),
                            'other_cost' => (float) ($transaction->other_cost ?? 0),
                            'grand_total' => (float) ($transaction->grand_total ?? 0),
                            'paid_amount' => (float) ($transaction->paid_amount ?? 0),
                            'remaining_amount' => (float) ($transaction->remaining_amount ?? 0),
                            'status' => $transaction->status,
                            'source' => $transaction->source,
                            'submitted_at' => $transaction->submitted_at,
                            'approved_at' => $transaction->approved_at,
                            'transaction_created_at' => $transaction->created_at,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            });
    }

    private function syncFactStockSnapshots(): void
    {
        $snapshotDate = now();
        $dateKey = $this->ensureDateDimension($snapshotDate);

        StockBalance::query()
            ->chunkById(200, function ($balances) use ($dateKey, $snapshotDate): void {
                foreach ($balances as $balance) {
                    $productDimId = $this->getProductDimId((int) $balance->product_id);
                    $warehouseDimId = $this->getWarehouseDimId((int) $balance->warehouse_id);

                    if (! $productDimId || ! $warehouseDimId) {
                        continue;
                    }

                    $qtyOnHand = (float) ($balance->qty_on_hand ?? 0);
                    $qtyReserved = (float) ($balance->qty_reserved ?? 0);
                    $minimumStock = (float) ($balance->minimum_stock ?? 0);
                    $qtyAvailable = $qtyOnHand - $qtyReserved;

                    $stockStatus = match (true) {
                        $qtyAvailable <= 0 => 'habis',
                        $qtyAvailable <= $minimumStock => 'menipis',
                        default => 'aman',
                    };

                    DB::table('dw_fact_stock_snapshots')->updateOrInsert(
                        [
                            'date_key' => $dateKey,
                            'product_dim_id' => $productDimId,
                            'warehouse_dim_id' => $warehouseDimId,
                        ],
                        $this->filterColumns('dw_fact_stock_snapshots', [
                            'qty_on_hand' => $qtyOnHand,
                            'qty_reserved' => $qtyReserved,
                            'qty_available' => $qtyAvailable,
                            'minimum_stock' => $minimumStock,
                            'stock_status' => $stockStatus,
                            'snapshot_at' => $snapshotDate,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            });
    }

    private function syncDimAssetCategories(): void
    {
        AssetCategory::query()
            ->chunkById(200, function ($categories): void {
                foreach ($categories as $category) {
                    DB::table('dw_dim_asset_categories')->updateOrInsert(
                        ['source_asset_category_id' => $category->id],
                        $this->filterColumns('dw_dim_asset_categories', [
                            'code' => $category->code,
                            'name' => $category->name,
                            'is_active' => (bool) ($category->is_active ?? true),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            });
    }

    private function syncDimAssetLocations(): void
    {
        AssetLocation::query()
            ->chunkById(200, function ($locations): void {
                foreach ($locations as $location) {
                    DB::table('dw_dim_asset_locations')->updateOrInsert(
                        ['source_asset_location_id' => $location->id],
                        $this->filterColumns('dw_dim_asset_locations', [
                            'code' => $location->code,
                            'name' => $location->name,
                            'address' => $location->address,
                            'is_active' => (bool) ($location->is_active ?? true),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            });
    }

    private function syncDimAssets(): void
    {
        Asset::query()
            ->with(['category', 'location'])
            ->chunkById(200, function ($assets): void {
                foreach ($assets as $asset) {
                    $categoryDimId = $asset->asset_category_id
                        ? $this->getAssetCategoryDimId((int) $asset->asset_category_id)
                        : null;

                    $locationDimId = $asset->asset_location_id
                        ? $this->getAssetLocationDimId((int) $asset->asset_location_id)
                        : null;

                    DB::table('dw_dim_assets')->updateOrInsert(
                        ['source_asset_id' => $asset->id],
                        $this->filterColumns('dw_dim_assets', [
                            'asset_category_dim_id' => $categoryDimId,
                            'asset_location_dim_id' => $locationDimId,
                            'asset_code' => $asset->asset_code,
                            'name' => $asset->name,
                            'license_plate' => $asset->license_plate,
                            'brand' => $asset->brand,
                            'model' => $asset->model,
                            'serial_number' => $asset->serial_number,
                            'acquisition_year' => $asset->acquisition_year,
                            'acquisition_date' => $asset->acquisition_date,
                            'acquisition_price' => (float) ($asset->acquisition_price ?? 0),
                            'condition' => $asset->condition,
                            'status' => $asset->status,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            });
    }

    private function syncFactAssetSnapshots(): void
    {
        $snapshotDate = now();
        $dateKey = $this->ensureDateDimension($snapshotDate);

        Asset::query()
            ->chunkById(200, function ($assets) use ($dateKey, $snapshotDate): void {
                foreach ($assets as $asset) {
                    $assetDimId = $this->getAssetDimId((int) $asset->id);

                    if (! $assetDimId) {
                        continue;
                    }

                    $categoryDimId = $asset->asset_category_id
                        ? $this->getAssetCategoryDimId((int) $asset->asset_category_id)
                        : null;

                    $locationDimId = $asset->asset_location_id
                        ? $this->getAssetLocationDimId((int) $asset->asset_location_id)
                        : null;

                    DB::table('dw_fact_asset_snapshots')->updateOrInsert(
                        [
                            'date_key' => $dateKey,
                            'asset_dim_id' => $assetDimId,
                        ],
                        $this->filterColumns('dw_fact_asset_snapshots', [
                            'asset_category_dim_id' => $categoryDimId,
                            'asset_location_dim_id' => $locationDimId,
                            'acquisition_price' => (float) ($asset->acquisition_price ?? 0),
                            'condition' => $asset->condition,
                            'status' => $asset->status,
                            'is_active' => $asset->status !== 'tidak_aktif',
                            'snapshot_at' => $snapshotDate,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            });
    }

    private function ensureDateDimension($date): int
    {
        $carbon = Carbon::parse($date)->startOfDay();
        $dateKey = (int) $carbon->format('Ymd');

        DB::table('dw_dim_dates')->updateOrInsert(
            ['date_key' => $dateKey],
            $this->filterColumns('dw_dim_dates', [
                'full_date' => $carbon->toDateString(),
                'day' => (int) $carbon->format('d'),
                'month' => (int) $carbon->format('m'),
                'month_name' => $carbon->translatedFormat('F'),
                'quarter' => (int) ceil(((int) $carbon->format('m')) / 3),
                'year' => (int) $carbon->format('Y'),
                'day_name' => $carbon->translatedFormat('l'),
                'is_weekend' => $carbon->isWeekend(),
                'created_at' => now(),
                'updated_at' => now(),
            ])
        );

        return $dateKey;
    }

    private function upsertInventoryMovement(array $uniqueBy, array $values): void
    {
        $uniqueBy = $this->filterColumns('dw_fact_inventory_movements', $uniqueBy);
        $values = $this->filterColumns('dw_fact_inventory_movements', $values);

        if ($uniqueBy === [] || $values === []) {
            return;
        }

        DB::table('dw_fact_inventory_movements')->updateOrInsert($uniqueBy, $values);
    }

    private function inventoryMovementUniqueKey(string $movementNumber, ?int $sourceStockMovementId = null): array
    {
        if (Schema::hasColumn('dw_fact_inventory_movements', 'movement_number')) {
            return ['movement_number' => $movementNumber];
        }

        if ($sourceStockMovementId !== null && Schema::hasColumn('dw_fact_inventory_movements', 'source_stock_movement_id')) {
            return ['source_stock_movement_id' => $sourceStockMovementId];
        }

        return [];
    }

    private function syntheticSourceStockMovementId(string $type, int $itemId): ?int
    {
        if (! Schema::hasColumn('dw_fact_inventory_movements', 'source_stock_movement_id')) {
            return null;
        }

        /*
         * Transaction items do not have rows in stock_movements when operational
         * stock updates are disabled. Keep a deterministic, non-null surrogate ID
         * so the same item is idempotent on both MySQL and SQLite test databases.
         */
        return $type === 'in'
            ? 1000000000 + $itemId
            : 2000000000 + $itemId;
    }

    private function applyApprovedTransactionFilter($query, string $tableName, string $alias): void
    {
        if (! Schema::hasColumn($tableName, 'status')) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where($alias . '.status', self::APPROVED_TRANSACTION_STATUS);
    }

    private function deleteUnapprovedTransactionFacts(
        string $factTable,
        string $sourceIdColumn,
        string $sourceTable
    ): void {
        if (
            ! Schema::hasTable($factTable)
            || ! Schema::hasColumn($factTable, $sourceIdColumn)
            || ! Schema::hasColumn($factTable, 'status')
            || ! Schema::hasTable($sourceTable)
            || ! Schema::hasColumn($sourceTable, 'status')
        ) {
            return;
        }

        DB::table($factTable)
            ->where(function ($query): void {
                $query
                    ->whereNull('status')
                    ->orWhere('status', '!=', self::APPROVED_TRANSACTION_STATUS);
            })
            ->delete();

        DB::table($factTable)
            ->whereIn($sourceIdColumn, function ($query) use ($sourceTable): void {
                $query
                    ->select('id')
                    ->from($sourceTable)
                    ->where(function ($statusQuery): void {
                        $statusQuery
                            ->whereNull('status')
                            ->orWhere('status', '!=', self::APPROVED_TRANSACTION_STATUS);
                    });
            })
            ->delete();
    }

    private function startEtlRun(Carbon $startedAt): ?int
    {
        if (! Schema::hasTable('dw_etl_runs')) {
            return null;
        }

        try {
            return DB::table('dw_etl_runs')->insertGetId([
                'run_uuid' => (string) Str::uuid(),
                'status' => 'running',
                'dimension_rows' => 0,
                'fact_rows' => 0,
                'table_row_counts' => null,
                'error_message' => null,
                'started_at' => $startedAt,
                'finished_at' => null,
                'duration_ms' => null,
                'created_at' => $startedAt,
                'updated_at' => $startedAt,
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function completeEtlRun(?int $etlRunId, Carbon $startedAt): void
    {
        if (! $etlRunId || ! Schema::hasTable('dw_etl_runs')) {
            return;
        }

        try {
            $finishedAt = now();
            $rowCounts = $this->dataWarehouseRowCounts();

            DB::table('dw_etl_runs')
                ->where('id', $etlRunId)
                ->update([
                    'status' => 'success',
                    'dimension_rows' => $this->sumTableRows($rowCounts, self::DIMENSION_TABLES),
                    'fact_rows' => $this->sumTableRows($rowCounts, self::FACT_TABLES),
                    'table_row_counts' => json_encode($rowCounts, JSON_THROW_ON_ERROR),
                    'error_message' => null,
                    'finished_at' => $finishedAt,
                    'duration_ms' => $this->durationInMilliseconds($startedAt, $finishedAt),
                    'updated_at' => $finishedAt,
                ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function failEtlRun(?int $etlRunId, Carbon $startedAt, Throwable $exception): void
    {
        if (! $etlRunId || ! Schema::hasTable('dw_etl_runs')) {
            return;
        }

        try {
            $finishedAt = now();

            DB::table('dw_etl_runs')
                ->where('id', $etlRunId)
                ->update([
                    'status' => 'failed',
                    'error_message' => mb_substr($exception->getMessage(), 0, 65535),
                    'finished_at' => $finishedAt,
                    'duration_ms' => $this->durationInMilliseconds($startedAt, $finishedAt),
                    'updated_at' => $finishedAt,
                ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function dataWarehouseRowCounts(): array
    {
        $rowCounts = [];

        foreach (array_merge(self::DIMENSION_TABLES, self::FACT_TABLES) as $table) {
            if (Schema::hasTable($table)) {
                $rowCounts[$table] = DB::table($table)->count();
            }
        }

        return $rowCounts;
    }

    private function sumTableRows(array $rowCounts, array $tables): int
    {
        return array_sum(array_map(
            fn (string $table): int => (int) ($rowCounts[$table] ?? 0),
            $tables
        ));
    }

    private function durationInMilliseconds(Carbon $startedAt, Carbon $finishedAt): int
    {
        return max(0, (int) round($startedAt->diffInMilliseconds($finishedAt)));
    }

    private function dateFromTransaction($transactionDate, $transactionCreatedAt, $itemCreatedAt): Carbon
    {
        $value = $transactionDate ?: $transactionCreatedAt ?: $itemCreatedAt ?: now();

        return Carbon::parse($value);
    }

    private function warehouseExpression(
        string $itemTable,
        string $transactionTable,
        string $itemAlias,
        string $transactionAlias
    ): ?string {
        $hasItemWarehouse = Schema::hasColumn($itemTable, 'warehouse_id');
        $hasTransactionWarehouse = Schema::hasColumn($transactionTable, 'warehouse_id');

        if ($hasItemWarehouse && $hasTransactionWarehouse) {
            return 'COALESCE(' . $itemAlias . '.warehouse_id, ' . $transactionAlias . '.warehouse_id)';
        }

        if ($hasItemWarehouse) {
            return $itemAlias . '.warehouse_id';
        }

        if ($hasTransactionWarehouse) {
            return $transactionAlias . '.warehouse_id';
        }

        return null;
    }

    private function filterColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn ($value, string $column): bool => Schema::hasColumn($table, $column))
            ->all();
    }

    private function getAssetCategoryDimId(?int $sourceId): ?int
    {
        return $sourceId
            ? DB::table('dw_dim_asset_categories')->where('source_asset_category_id', $sourceId)->value('id')
            : null;
    }

    private function getAssetLocationDimId(?int $sourceId): ?int
    {
        return $sourceId
            ? DB::table('dw_dim_asset_locations')->where('source_asset_location_id', $sourceId)->value('id')
            : null;
    }

    private function getAssetDimId(?int $sourceId): ?int
    {
        return $sourceId
            ? DB::table('dw_dim_assets')->where('source_asset_id', $sourceId)->value('id')
            : null;
    }

    private function getProductDimId(?int $sourceId): ?int
    {
        return $sourceId
            ? DB::table('dw_dim_products')->where('source_product_id', $sourceId)->value('id')
            : null;
    }

    private function getWarehouseDimId(?int $sourceId): ?int
    {
        return $sourceId
            ? DB::table('dw_dim_warehouses')->where('source_warehouse_id', $sourceId)->value('id')
            : null;
    }

    private function getSupplierDimId(?int $sourceId): ?int
    {
        return $sourceId
            ? DB::table('dw_dim_suppliers')->where('source_supplier_id', $sourceId)->value('id')
            : null;
    }

    private function getCustomerDimId(?int $sourceId): ?int
    {
        return $sourceId
            ? DB::table('dw_dim_customers')->where('source_customer_id', $sourceId)->value('id')
            : null;
    }

    private function getUserDimId(?int $sourceId): ?int
    {
        return $sourceId
            ? DB::table('dw_dim_users')->where('source_user_id', $sourceId)->value('id')
            : null;
    }
}
