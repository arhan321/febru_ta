<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

final class MobileStockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $warehouseId = $request->query('warehouse_id');
        $productId = $request->query('product_id');
        $search = trim((string) $request->query('search', ''));

        $data = DB::table('stock_balances')
            ->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_balances.warehouse_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->leftJoin('product_types', 'product_types.id', '=', 'products.product_type_id')
            ->leftJoin('product_densities', 'product_densities.id', '=', 'products.product_density_id')
            ->leftJoin('product_categories', 'product_categories.id', '=', 'products.product_category_id')
            ->where('products.is_active', true)
            ->where('warehouses.is_active', true)
            ->when($warehouseId, function ($query) use ($warehouseId): void {
                $query->where('stock_balances.warehouse_id', $warehouseId);
            })
            ->when($productId, function ($query) use ($productId): void {
                $query->where('stock_balances.product_id', $productId);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('products.code', 'like', "%{$search}%")
                        ->orWhere('products.name', 'like', "%{$search}%")
                        ->orWhere('products.full_name', 'like', "%{$search}%")
                        ->orWhere('products.size_text', 'like', "%{$search}%")
                        ->orWhere('warehouses.name', 'like', "%{$search}%")
                        ->orWhere('warehouses.code', 'like', "%{$search}%")
                        ->orWhere('units.name', 'like', "%{$search}%")
                        ->orWhere('product_types.name', 'like', "%{$search}%")
                        ->orWhere('product_densities.name', 'like', "%{$search}%")
                        ->orWhere('product_categories.name', 'like', "%{$search}%");
                });
            })
            ->orderBy('products.name')
            ->get([
                'stock_balances.id',
                'stock_balances.product_id',
                'stock_balances.warehouse_id',
                'stock_balances.qty_on_hand',
                'stock_balances.qty_reserved',
                'stock_balances.minimum_stock',

                'products.code as product_code',
                'products.name as product_name',
                'products.full_name as product_full_name',
                'products.size_text as product_size_text',
                'products.length',
                'products.width',
                'products.thickness',
                'products.unit_id',
                'products.product_type_id',
                'products.product_density_id',
                'products.product_category_id',
                'products.logo_path',
                'products.default_purchase_price',
                'products.default_selling_price',
                'products.last_purchase_price',
                'products.last_selling_price',

                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse_name',

                'units.name as unit_name',
                'product_types.name as type_name',
                'product_densities.name as density_name',
                'product_categories.name as category_name',
            ])
            ->map(function ($stock): array {
                $qtyOnHand = (float) ($stock->qty_on_hand ?? 0);
                $qtyReserved = (float) ($stock->qty_reserved ?? 0);
                $availableQty = max($qtyOnHand - $qtyReserved, 0);
                $minimumStock = (float) ($stock->minimum_stock ?? 0);

                $displayName = $stock->product_full_name ?: $stock->product_name;

                $unitName = $stock->unit_name ?: 'PCS';
                $typeName = $stock->type_name ?: 'UMUM';
                $densityName = $stock->density_name ?: 'UMUM';
                $categoryName = $stock->category_name ?: 'UMUM';

                $logoUrl = $this->productLogoUrl($stock->logo_path);

                return [
                    'id' => (int) $stock->id,
                    'product_id' => (int) $stock->product_id,
                    'warehouse_id' => (int) $stock->warehouse_id,

                    'product_code' => $stock->product_code,
                    'code' => $stock->product_code,

                    'product_name' => $stock->product_name,
                    'name' => $displayName,
                    'product_display_name' => $displayName,
                    'display_name' => $displayName,

                    'product_size_text' => $stock->product_size_text,
                    'size_text' => $stock->product_size_text,

                    'length' => $stock->length !== null ? (float) $stock->length : null,
                    'width' => $stock->width !== null ? (float) $stock->width : null,
                    'thickness' => $stock->thickness !== null ? (float) $stock->thickness : null,

                    'unit_id' => $stock->unit_id,
                    'unit_name' => $unitName,

                    'product_type_id' => $stock->product_type_id,
                    'product_density_id' => $stock->product_density_id,
                    'product_category_id' => $stock->product_category_id,

                    'type_name' => $typeName,
                    'product_type_name' => $typeName,

                    'density_name' => $densityName,
                    'product_density_name' => $densityName,

                    'category_name' => $categoryName,
                    'product_category_name' => $categoryName,

                    'warehouse_code' => $stock->warehouse_code,
                    'warehouse_name' => $stock->warehouse_name,

                    'qty_on_hand' => $qtyOnHand,
                    'qty_reserved' => $qtyReserved,
                    'available_qty' => $availableQty,
                    'qty_available' => $availableQty,
                    'available_stock' => $availableQty,
                    'stock' => $availableQty,

                    'minimum_stock' => $minimumStock,
                    'is_low_stock' => $availableQty <= $minimumStock,

                    'default_purchase_price' => (float) ($stock->default_purchase_price ?? 0),
                    'default_selling_price' => (float) ($stock->default_selling_price ?? 0),
                    'last_purchase_price' => (float) ($stock->last_purchase_price ?? 0),
                    'last_selling_price' => (float) ($stock->last_selling_price ?? 0),

                    'logo_path' => $stock->logo_path,
                    'product_logo_path' => $stock->logo_path,
                    'logo_url' => $logoUrl,
                    'product_logo_url' => $logoUrl,

                    'product' => [
                        'id' => (int) $stock->product_id,
                        'code' => $stock->product_code,
                        'name' => $stock->product_name,
                        'full_name' => $displayName,
                        'display_name' => $displayName,
                        'size_text' => $stock->product_size_text,

                        'unit_id' => $stock->unit_id,
                        'unit_name' => $unitName,

                        'product_type_id' => $stock->product_type_id,
                        'product_density_id' => $stock->product_density_id,
                        'product_category_id' => $stock->product_category_id,

                        'type_name' => $typeName,
                        'product_type_name' => $typeName,

                        'density_name' => $densityName,
                        'product_density_name' => $densityName,

                        'category_name' => $categoryName,
                        'product_category_name' => $categoryName,

                        'logo_path' => $stock->logo_path,
                        'product_logo_path' => $stock->logo_path,
                        'logo_url' => $logoUrl,
                        'product_logo_url' => $logoUrl,
                    ],
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
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
            str_contains($baseUrl, '127.0.0.1') ||
            str_contains($baseUrl, '0.0.0.0')
        ) {
            $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        }

        return $baseUrl . '/storage/' . $logoPath;
    }
}