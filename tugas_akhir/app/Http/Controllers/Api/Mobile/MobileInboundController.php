<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

final class MobileInboundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $status = $request->query('status');
        $search = $request->query('search');

        $data = DB::table('inbound_transactions')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'inbound_transactions.supplier_id')
            ->join('warehouses', 'warehouses.id', '=', 'inbound_transactions.warehouse_id')
            ->where('inbound_transactions.submitted_by', $user->id)
            ->when($status, function ($query) use ($status): void {
                $query->where('inbound_transactions.status', $status);
            })
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('inbound_transactions.transaction_number', 'like', "%{$search}%")
                        ->orWhere('inbound_transactions.invoice_number', 'like', "%{$search}%")
                        ->orWhere('suppliers.name', 'like', "%{$search}%")
                        ->orWhere('warehouses.name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('inbound_transactions.transaction_date')
            ->orderByDesc('inbound_transactions.id')
            ->get([
                'inbound_transactions.id',
                'inbound_transactions.transaction_number',
                'inbound_transactions.transaction_date',
                'inbound_transactions.invoice_number',
                'inbound_transactions.status',
                'inbound_transactions.note',
                'inbound_transactions.grand_total',
                'inbound_transactions.submitted_at',
                'inbound_transactions.approved_at',
                'inbound_transactions.rejected_at',
                'inbound_transactions.rejection_reason',
                'suppliers.id as supplier_id',
                'suppliers.name as supplier_name',
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
            ])
            ->map(function ($transaction): array {
                $items = DB::table('inbound_transaction_items')
                    ->where('inbound_transaction_id', $transaction->id)
                    ->get(['qty']);

                $totalQty = $items->sum(fn ($item) => (float) $item->qty);

                return [
                    'id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'transaction_date' => $transaction->transaction_date,
                    'invoice_number' => $transaction->invoice_number,
                    'status' => $transaction->status,
                    'supplier' => [
                        'id' => $transaction->supplier_id,
                        'name' => $transaction->supplier_name,
                    ],
                    'warehouse' => [
                        'id' => $transaction->warehouse_id,
                        'name' => $transaction->warehouse_name,
                    ],
                    'total_items' => $items->count(),
                    'total_qty' => $totalQty,
                    'grand_total' => (float) $transaction->grand_total,
                    'note' => $transaction->note,
                    'submitted_at' => $transaction->submitted_at,
                    'approved_at' => $transaction->approved_at,
                    'rejected_at' => $transaction->rejected_at,
                    'rejection_reason' => $transaction->rejection_reason,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'note' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.note' => ['nullable', 'string'],

            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $user = $request->user();

        $transaction = DB::transaction(function () use ($request, $validated, $user) {
            $transactionNumber = $this->generateTransactionNumber();

            $supplierId = $this->resolveSupplierId(
                isset($validated['supplier_id']) ? (int) $validated['supplier_id'] : null,
                $validated['supplier_name'] ?? null,
            );

            if ($supplierId === null) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Nama supplier wajib diisi.',
                    'errors' => [
                        'supplier_name' => ['Nama supplier wajib diisi.'],
                    ],
                ], 422));
            }

            $subTotal = 0;

            foreach ($validated['items'] as $item) {
                $product = DB::table('products')
                    ->where('id', $item['product_id'])
                    ->first([
                        'id',
                        'code',
                        'name',
                        'full_name',
                        'unit_id',
                        'last_purchase_price',
                        'default_purchase_price',
                    ]);

                $qty = (float) $item['qty'];
                $unitCost = isset($item['unit_cost'])
                    ? (float) $item['unit_cost']
                    : (float) ($product->last_purchase_price ?: $product->default_purchase_price ?: 0);

                $subTotal += $qty * $unitCost;
            }

            $transactionId = DB::table('inbound_transactions')->insertGetId([
                'transaction_number' => $transactionNumber,
                'transaction_date' => $validated['transaction_date'],
                'invoice_number' => $validated['invoice_number'] ?? null,
                'supplier_id' => $supplierId,
                'warehouse_id' => $validated['warehouse_id'],
                'note' => $validated['note'] ?? null,
                'status' => 'pending',
                'sub_total' => $subTotal,
                'discount_amount' => 0,
                'other_cost' => 0,
                'grand_total' => $subTotal,
                'submitted_by' => $user->id,
                'submitted_at' => now(),
                'source' => 'mobile',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                $product = DB::table('products')
                    ->where('id', $item['product_id'])
                    ->first([
                        'id',
                        'code',
                        'name',
                        'full_name',
                        'unit_id',
                        'last_purchase_price',
                        'default_purchase_price',
                    ]);

                $qty = (float) $item['qty'];
                $unitCost = isset($item['unit_cost'])
                    ? (float) $item['unit_cost']
                    : (float) ($product->last_purchase_price ?: $product->default_purchase_price ?: 0);

                $subtotal = $qty * $unitCost;

                $unitName = null;

                if ($product->unit_id) {
                    $unitName = DB::table('units')
                        ->where('id', $product->unit_id)
                        ->value('name');
                }

                DB::table('inbound_transaction_items')->insert([
                    'inbound_transaction_id' => $transactionId,
                    'product_id' => $product->id,
                    'warehouse_id' => $validated['warehouse_id'],
                    'unit_id' => $product->unit_id,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                    'product_code_snapshot' => $product->code,
                    'product_name_snapshot' => $product->full_name ?: $product->name,
                    'unit_name_snapshot' => $unitName,
                    'note' => $item['note'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('inventory/inbounds/'.$transactionId, 'public');

                    DB::table('inbound_transaction_attachments')->insert([
                        'inbound_transaction_id' => $transactionId,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return DB::table('inbound_transactions')
                ->where('id', $transactionId)
                ->first();
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan barang masuk berhasil dikirim. Menunggu approval admin.',
            'data' => [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'transaction_date' => $transaction->transaction_date,
                'status' => $transaction->status,
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'note' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.note' => ['nullable', 'string'],

            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $user = $request->user();

        $transaction = DB::table('inbound_transactions')
            ->where('id', $id)
            ->where('submitted_by', $user->id)
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Data barang masuk tidak ditemukan.',
            ], 404);
        }

        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan barang masuk hanya bisa diedit saat status masih pending.',
            ], 422);
        }

        $existingAttachmentCount = DB::table('inbound_transaction_attachments')
            ->where('inbound_transaction_id', $id)
            ->count();

        $newAttachmentCount = $request->hasFile('attachments')
            ? count($request->file('attachments'))
            : 0;

        if (($existingAttachmentCount + $newAttachmentCount) > 3) {
            return response()->json([
                'success' => false,
                'message' => 'Total lampiran maksimal 3 file. Hapus lampiran lama melalui dashboard admin atau jangan tambahkan lampiran baru.',
            ], 422);
        }

        $updatedTransaction = DB::transaction(function () use ($request, $validated, $id, $user) {
            $supplierId = $this->resolveSupplierId(
                isset($validated['supplier_id']) ? (int) $validated['supplier_id'] : null,
                $validated['supplier_name'] ?? null,
            );

            if ($supplierId === null) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Nama supplier wajib diisi.',
                    'errors' => [
                        'supplier_name' => ['Nama supplier wajib diisi.'],
                    ],
                ], 422));
            }

            $subTotal = 0;

            foreach ($validated['items'] as $item) {
                $product = DB::table('products')
                    ->where('id', $item['product_id'])
                    ->first([
                        'id',
                        'code',
                        'name',
                        'full_name',
                        'unit_id',
                        'last_purchase_price',
                        'default_purchase_price',
                    ]);

                $qty = (float) $item['qty'];
                $unitCost = isset($item['unit_cost'])
                    ? (float) $item['unit_cost']
                    : (float) ($product->last_purchase_price ?: $product->default_purchase_price ?: 0);

                $subTotal += $qty * $unitCost;
            }

            DB::table('inbound_transactions')
                ->where('id', $id)
                ->where('submitted_by', $user->id)
                ->update([
                    'transaction_date' => $validated['transaction_date'],
                    'invoice_number' => $validated['invoice_number'] ?? null,
                    'supplier_id' => $supplierId,
                    'warehouse_id' => $validated['warehouse_id'],
                    'note' => $validated['note'] ?? null,
                    'sub_total' => $subTotal,
                    'discount_amount' => 0,
                    'other_cost' => 0,
                    'grand_total' => $subTotal,
                    'updated_at' => now(),
                ]);

            DB::table('inbound_transaction_items')
                ->where('inbound_transaction_id', $id)
                ->delete();

            foreach ($validated['items'] as $item) {
                $product = DB::table('products')
                    ->where('id', $item['product_id'])
                    ->first([
                        'id',
                        'code',
                        'name',
                        'full_name',
                        'unit_id',
                        'last_purchase_price',
                        'default_purchase_price',
                    ]);

                $qty = (float) $item['qty'];
                $unitCost = isset($item['unit_cost'])
                    ? (float) $item['unit_cost']
                    : (float) ($product->last_purchase_price ?: $product->default_purchase_price ?: 0);

                $subtotal = $qty * $unitCost;

                $unitName = null;

                if ($product->unit_id) {
                    $unitName = DB::table('units')
                        ->where('id', $product->unit_id)
                        ->value('name');
                }

                DB::table('inbound_transaction_items')->insert([
                    'inbound_transaction_id' => $id,
                    'product_id' => $product->id,
                    'warehouse_id' => $validated['warehouse_id'],
                    'unit_id' => $product->unit_id,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                    'product_code_snapshot' => $product->code,
                    'product_name_snapshot' => $product->full_name ?: $product->name,
                    'unit_name_snapshot' => $unitName,
                    'note' => $item['note'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('inventory/inbounds/'.$id, 'public');

                    DB::table('inbound_transaction_attachments')->insert([
                        'inbound_transaction_id' => $id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return DB::table('inbound_transactions')
                ->where('id', $id)
                ->first();
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan barang masuk berhasil diperbarui.',
            'data' => [
                'id' => $updatedTransaction->id,
                'transaction_number' => $updatedTransaction->transaction_number,
                'transaction_date' => $updatedTransaction->transaction_date,
                'invoice_number' => $updatedTransaction->invoice_number,
                'status' => $updatedTransaction->status,
                'sub_total' => (float) $updatedTransaction->sub_total,
                'grand_total' => (float) $updatedTransaction->grand_total,
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $transaction = DB::table('inbound_transactions')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'inbound_transactions.supplier_id')
            ->join('warehouses', 'warehouses.id', '=', 'inbound_transactions.warehouse_id')
            ->leftJoin('users as submitter', 'submitter.id', '=', 'inbound_transactions.submitted_by')
            ->where('inbound_transactions.id', $id)
            ->where('inbound_transactions.submitted_by', $user->id)
            ->first([
                'inbound_transactions.id',
                'inbound_transactions.transaction_number',
                'inbound_transactions.transaction_date',
                'inbound_transactions.invoice_number',
                'inbound_transactions.status',
                'inbound_transactions.note',
                'inbound_transactions.sub_total',
                'inbound_transactions.discount_amount',
                'inbound_transactions.other_cost',
                'inbound_transactions.grand_total',
                'inbound_transactions.submitted_at',
                'inbound_transactions.approved_at',
                'inbound_transactions.rejected_at',
                'inbound_transactions.rejection_reason',
                'inbound_transactions.approval_note',
                'suppliers.id as supplier_id',
                'suppliers.code as supplier_code',
                'suppliers.name as supplier_name',
                'warehouses.id as warehouse_id',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse_name',
                'submitter.id as submitted_by_id',
                'submitter.name as submitted_by_name',
            ]);

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Data barang masuk tidak ditemukan.',
            ], 404);
        }

        $items = DB::table('inbound_transaction_items')
            ->leftJoin('products', 'products.id', '=', 'inbound_transaction_items.product_id')
            ->where('inbound_transaction_items.inbound_transaction_id', $transaction->id)
            ->orderBy('inbound_transaction_items.id')
            ->get([
                'inbound_transaction_items.id',
                'inbound_transaction_items.product_id',
                'inbound_transaction_items.warehouse_id',
                'inbound_transaction_items.unit_id',
                'inbound_transaction_items.qty',
                'inbound_transaction_items.unit_cost',
                'inbound_transaction_items.subtotal',
                'inbound_transaction_items.product_code_snapshot',
                'inbound_transaction_items.product_name_snapshot',
                'inbound_transaction_items.unit_name_snapshot',
                'inbound_transaction_items.note',
                'products.code as current_product_code',
                'products.name as current_product_name',
                'products.full_name as current_product_full_name',
                'products.size_text as product_size_text',
                'products.logo_path as product_logo_path',
            ])
            ->map(function ($item): array {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $item->warehouse_id,
                    'unit_id' => $item->unit_id,
                    'product_code' => $item->product_code_snapshot ?: $item->current_product_code,
                    'product_name' => $item->product_name_snapshot ?: ($item->current_product_full_name ?: $item->current_product_name),
                    'product_size_text' => $item->product_size_text,
                    'logo_path' => $item->product_logo_path,
                    'product_logo_path' => $item->product_logo_path,
                    'logo_url' => $this->productLogoUrl($item->product_logo_path),
                    'product_logo_url' => $this->productLogoUrl($item->product_logo_path),
                    'unit_name' => $item->unit_name_snapshot,
                    'qty' => (float) $item->qty,
                    'unit_cost' => (float) $item->unit_cost,
                    'subtotal' => (float) $item->subtotal,
                    'note' => $item->note,
                ];
            });

        $attachments = DB::table('inbound_transaction_attachments')
            ->where('inbound_transaction_id', $transaction->id)
            ->orderBy('id')
            ->get([
                'id',
                'file_path',
                'file_name',
                'mime_type',
                'file_size',
                'uploaded_by',
                'created_at',
            ])
            ->map(function ($attachment): array {
                return [
                    'id' => $attachment->id,
                    'file_path' => $attachment->file_path,
                    'file_url' => asset('storage/'.$attachment->file_path),
                    'file_name' => $attachment->file_name,
                    'mime_type' => $attachment->mime_type,
                    'file_size' => $attachment->file_size,
                    'uploaded_by' => $attachment->uploaded_by,
                    'created_at' => $attachment->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'transaction_date' => $transaction->transaction_date,
                'invoice_number' => $transaction->invoice_number,
                'status' => $transaction->status,
                'supplier' => [
                    'id' => $transaction->supplier_id,
                    'code' => $transaction->supplier_code,
                    'name' => $transaction->supplier_name,
                ],
                'warehouse' => [
                    'id' => $transaction->warehouse_id,
                    'code' => $transaction->warehouse_code,
                    'name' => $transaction->warehouse_name,
                ],
                'submitted_by' => [
                    'id' => $transaction->submitted_by_id,
                    'name' => $transaction->submitted_by_name,
                ],
                'items' => $items,
                'attachments' => $attachments,
                'summary' => [
                    'total_items' => $items->count(),
                    'total_qty' => $items->sum(fn ($item) => (float) $item['qty']),
                    'sub_total' => (float) $transaction->sub_total,
                    'discount_amount' => (float) $transaction->discount_amount,
                    'other_cost' => (float) $transaction->other_cost,
                    'grand_total' => (float) $transaction->grand_total,
                ],
                'note' => $transaction->note,
                'submitted_at' => $transaction->submitted_at,
                'approved_at' => $transaction->approved_at,
                'rejected_at' => $transaction->rejected_at,
                'rejection_reason' => $transaction->rejection_reason,
                'approval_note' => $transaction->approval_note,
            ],
        ]);
    }

    private function resolveSupplierId(?int $supplierId, ?string $supplierName): ?int
    {
        if ($supplierId !== null && $supplierId > 0) {
            return $supplierId;
        }

        $supplierName = trim((string) $supplierName);

        if ($supplierName === '') {
            return null;
        }

        $existingId = DB::table('suppliers')
            ->whereRaw('LOWER(name) = ?', [strtolower($supplierName)])
            ->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        $payload = [
            'name' => $supplierName,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (DB::getSchemaBuilder()->hasColumn('suppliers', 'code')) {
            $payload['code'] = $this->generateSupplierCode();
        }

        return (int) DB::table('suppliers')->insertGetId($payload);
    }

    private function generateSupplierCode(): string
    {
        do {
            $code = 'SUP-MOB-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
            $exists = DB::table('suppliers')->where('code', $code)->exists();
        } while ($exists);

        return $code;
    }

    private function productLogoUrl(?string $logoPath): ?string
    {
        if ($logoPath === null || trim($logoPath) === '') {
            return null;
        }

        $logoPath = trim($logoPath);
        $logoPath = str_replace('\\', '/', $logoPath);

        if (
            str_starts_with($logoPath, 'http://') ||
            str_starts_with($logoPath, 'https://')
        ) {
            return $logoPath;
        }

        $logoPath = ltrim($logoPath, '/');

        if (str_starts_with($logoPath, 'public/')) {
            $logoPath = substr($logoPath, strlen('public/'));
        }

        if (str_starts_with($logoPath, 'storage/')) {
            $logoPath = substr($logoPath, strlen('storage/'));
        }

        $baseUrl = rtrim((string) config('app.url'), '/');

        if (
            $baseUrl === '' ||
            str_contains($baseUrl, 'localhost') ||
            str_contains($baseUrl, '127.0.0.1')
        ) {
            $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        }

        return $baseUrl.'/storage/'.$logoPath;
    }

    private function generateTransactionNumber(): string
    {
        $prefix = 'BM-'.now()->format('Ym').'-';

        $lastNumber = DB::table('inbound_transactions')
            ->where('transaction_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('transaction_number')
            ->value('transaction_number');

        if (! $lastNumber) {
            return $prefix.'001';
        }

        $lastSequence = (int) Str::afterLast($lastNumber, '-');
        $nextSequence = $lastSequence + 1;

        return $prefix.str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
    }
}