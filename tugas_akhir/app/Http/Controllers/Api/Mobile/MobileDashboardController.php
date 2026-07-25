<?php

namespace App\Http\Controllers\Api\Mobile;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;

class MobileDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'message' => 'Dashboard berhasil diambil.',
            'data' => [
                'user_name' => $user?->name ?? 'Staff Gudang',
                'today_label' => now()->translatedFormat('d M Y'),
                'stock_summary' => $this->stockSummary(),
                'attention_items' => $this->attentionItems(),

                // Penting:
                // Aktivitas terbaru harus berdasarkan akun yang sedang login,
                // bukan mengambil transaksi global semua user.
                'recent_activities' => $this->recentActivities((int) $user->id),
            ],
        ]);
    }

    private function stockSummary(): array
    {
        $stockTable = $this->firstExistingTable([
            'warehouse_stocks',
            'product_stocks',
            'stock_gudangs',
            'stocks',
        ]);

        if (! $stockTable) {
            return [
                'total' => Schema::hasTable('products') ? DB::table('products')->count() : 0,
                'aman' => 0,
                'menipis' => 0,
                'habis' => 0,
            ];
        }

        $qtyColumn = $this->firstExistingColumn($stockTable, [
            'stock',
            'qty',
            'quantity',
            'current_stock',
            'available_stock',
            'stok',
        ]);

        $productColumn = $this->firstExistingColumn($stockTable, [
            'product_id',
            'produk_id',
        ]);

        if (! $qtyColumn || ! $productColumn) {
            return [
                'total' => 0,
                'aman' => 0,
                'menipis' => 0,
                'habis' => 0,
            ];
        }

        $rows = DB::table($stockTable)
            ->select($productColumn)
            ->selectRaw("SUM({$qtyColumn}) as total_stock")
            ->groupBy($productColumn)
            ->get();

        $total = $rows->count();
        $aman = 0;
        $menipis = 0;
        $habis = 0;

        foreach ($rows as $row) {
            $stock = (float) $row->total_stock;

            if ($stock <= 0) {
                $habis++;
            } elseif ($stock <= 10) {
                $menipis++;
            } else {
                $aman++;
            }
        }

        return [
            'total' => $total,
            'aman' => $aman,
            'menipis' => $menipis,
            'habis' => $habis,
        ];
    }

    private function attentionItems(): array
    {
        $stockTable = $this->firstExistingTable([
            'warehouse_stocks',
            'product_stocks',
            'stock_gudangs',
            'stocks',
        ]);

        if (! $stockTable || ! Schema::hasTable('products')) {
            return [];
        }

        $qtyColumn = $this->firstExistingColumn($stockTable, [
            'stock',
            'qty',
            'quantity',
            'current_stock',
            'available_stock',
            'stok',
        ]);

        $productColumn = $this->firstExistingColumn($stockTable, [
            'product_id',
            'produk_id',
        ]);

        $productNameColumn = $this->firstExistingColumn('products', [
            'name',
            'product_name',
        ]);

        if (! $qtyColumn || ! $productColumn || ! $productNameColumn) {
            return [];
        }

        return DB::table($stockTable)
            ->join('products', 'products.id', '=', "{$stockTable}.{$productColumn}")
            ->select("products.{$productNameColumn} as name")
            ->selectRaw("SUM({$stockTable}.{$qtyColumn}) as total_stock")
            ->groupBy('products.id', "products.{$productNameColumn}")
            ->havingRaw("SUM({$stockTable}.{$qtyColumn}) <= 10")
            ->orderByRaw("SUM({$stockTable}.{$qtyColumn}) ASC")
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $stock = (float) $row->total_stock;

                return [
                    'status' => $stock <= 0 ? 'Habis' : 'Menipis',
                    'name' => $row->name,
                    'qty' => number_format($stock, 0, ',', '.') . ' PCS',
                ];
            })
            ->values()
            ->toArray();
    }

    private function recentActivities(int $userId): array
    {
        $activities = collect();

        if ($userId <= 0) {
            return [];
        }

        if (Schema::hasTable('inbound_transactions')) {
            $inboundQuery = DB::table('inbound_transactions')
                ->select([
                    'id',
                    'transaction_number',
                    'invoice_number',
                    'transaction_date',
                    'created_at',
                ]);

            // Filter per akun login.
            // Kolom ini ada di transaksi mobile kamu karena saat store/update memakai submitted_by.
            if (Schema::hasColumn('inbound_transactions', 'submitted_by')) {
                $inboundQuery->where('submitted_by', $userId);
            }

            $activities = $activities->merge(
                $inboundQuery
                    ->latest('created_at')
                    ->limit(5)
                    ->get()
                    ->map(function ($row) {
                        return [
                            'type' => 'Barang Masuk',
                            'code' => $row->transaction_number ?? $row->invoice_number,
                            'qty' => $this->transactionQty(
                                'inbound_transaction_items',
                                'inbound_transaction_id',
                                (int) $row->id
                            ),
                            'time' => Carbon::parse($row->transaction_date)->translatedFormat('d M Y'),
                            'created_at' => $row->created_at,
                        ];
                    })
            );
        }

        if (Schema::hasTable('outbound_transactions')) {
            $outboundQuery = DB::table('outbound_transactions')
                ->select([
                    'id',
                    'transaction_number',
                    'reference_number',
                    'transaction_date',
                    'created_at',
                ]);

            // Filter per akun login.
            // Ini yang membuat akun A tidak bisa melihat aktivitas akun B.
            if (Schema::hasColumn('outbound_transactions', 'submitted_by')) {
                $outboundQuery->where('submitted_by', $userId);
            }

            $activities = $activities->merge(
                $outboundQuery
                    ->latest('created_at')
                    ->limit(5)
                    ->get()
                    ->map(function ($row) {
                        return [
                            'type' => 'Barang Keluar',
                            'code' => $row->transaction_number ?? $row->reference_number,
                            'qty' => $this->transactionQty(
                                'outbound_transaction_items',
                                'outbound_transaction_id',
                                (int) $row->id
                            ),
                            'time' => Carbon::parse($row->transaction_date)->translatedFormat('d M Y'),
                            'created_at' => $row->created_at,
                        ];
                    })
            );
        }

        return $activities
            ->sortByDesc('created_at')
            ->take(5)
            ->map(function ($item) {
                unset($item['created_at']);

                return $item;
            })
            ->values()
            ->toArray();
    }

    private function transactionQty(string $itemTable, string $foreignKey, int $transactionId): string
    {
        if (! Schema::hasTable($itemTable)) {
            return '0 PCS';
        }

        $qtyColumn = $this->firstExistingColumn($itemTable, [
            'qty',
            'quantity',
            'jumlah',
        ]);

        if (! $qtyColumn || ! Schema::hasColumn($itemTable, $foreignKey)) {
            return '0 PCS';
        }

        $qty = DB::table($itemTable)
            ->where($foreignKey, $transactionId)
            ->sum($qtyColumn);

        return number_format((float) $qty, 0, ',', '.') . ' PCS';
    }

    private function firstExistingTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}