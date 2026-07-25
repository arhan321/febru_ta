<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;

final class MobileMasterDataController extends Controller
{
    public function warehouses(): JsonResponse
    {
        $data = DB::table('warehouses')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'address', 'phone']);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function suppliers(): JsonResponse
    {
        $data = DB::table('suppliers')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'phone', 'address']);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function customers(): JsonResponse
    {
        $data = DB::table('customers')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'phone', 'address', 'customer_type']);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $typeNames = $this->masterMap('product_types');
        $densityNames = $this->masterMap('product_densities');
        $categoryNames = $this->masterMap('product_categories');
        $unitNames = $this->masterMap('units');

        $data = DB::table('products')
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('size_text', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'full_name',
                'logo_path',
                'product_type_id',
                'product_density_id',
                'product_category_id',
                'unit_id',
                'length',
                'width',
                'thickness',
                'size_text',
                'default_purchase_price',
                'default_selling_price',
                'last_purchase_price',
                'last_selling_price',
            ])
            ->map(function ($product) use (
                $typeNames,
                $densityNames,
                $categoryNames,
                $unitNames
            ): array {
                $defaultPurchasePrice = (float) ($product->default_purchase_price ?? 0);
                $lastPurchasePrice = (float) ($product->last_purchase_price ?? 0);

                $defaultSellingPrice = (float) ($product->default_selling_price ?? 0);
                $lastSellingPrice = (float) ($product->last_selling_price ?? 0);

                /*
                |--------------------------------------------------------------------------
                | Harga Satuan Default Mobile
                |--------------------------------------------------------------------------
                | Prioritas:
                | 1. last_purchase_price kalau ada
                | 2. default_purchase_price kalau ada
                | 3. 0
                */
                $unitCost = $lastPurchasePrice > 0
                    ? $lastPurchasePrice
                    : $defaultPurchasePrice;

                return [
                    'id' => (int) $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'display_name' => $product->full_name ?: $product->name,
                    'full_name' => $product->full_name,

                    'logo_path' => $product->logo_path,
                    'logo_url' => $this->productLogoUrl($product->logo_path),

                    'product_type_id' => $product->product_type_id,
                    'product_density_id' => $product->product_density_id,
                    'product_category_id' => $product->product_category_id,
                    'unit_id' => $product->unit_id,

                    'type_name' => $this->mapName($typeNames, $product->product_type_id),
                    'density_name' => $this->mapName($densityNames, $product->product_density_id),
                    'category_name' => $this->mapName($categoryNames, $product->product_category_id),
                    'unit_name' => $this->mapName($unitNames, $product->unit_id) ?: 'PCS',

                    'length' => $product->length !== null ? (float) $product->length : null,
                    'width' => $product->width !== null ? (float) $product->width : null,
                    'thickness' => $product->thickness !== null ? (float) $product->thickness : null,
                    'size_text' => $product->size_text,

                    'default_purchase_price' => $defaultPurchasePrice,
                    'default_selling_price' => $defaultSellingPrice,
                    'last_purchase_price' => $lastPurchasePrice,
                    'last_selling_price' => $lastSellingPrice,

                    /*
                    |--------------------------------------------------------------------------
                    | Alias Harga Untuk Flutter
                    |--------------------------------------------------------------------------
                    | Ini supaya mobile langsung bisa isi Harga Satuan otomatis.
                    */
                    'unit_cost' => $unitCost,
                    'default_unit_cost' => $unitCost,
                    'purchase_price' => $unitCost,
                    'buy_price' => $unitCost,
                    'cost_price' => $unitCost,
                    'harga_beli' => $unitCost,
                    'harga_satuan' => $unitCost,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function masterMap(string $table): Collection
    {
        if (! Schema::hasTable($table)) {
            return collect();
        }

        if (! Schema::hasColumn($table, 'id') || ! Schema::hasColumn($table, 'name')) {
            return collect();
        }

        return DB::table($table)->pluck('name', 'id');
    }

    private function mapName(Collection $map, mixed $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $value = $map->get($id)
            ?? $map->get((string) $id)
            ?? $map->get((int) $id);

        return $value !== null ? (string) $value : null;
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

        return $baseUrl . '/storage/' . $logoPath;
    }
}