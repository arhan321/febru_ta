<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class MobileProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['profile', 'roles']);
        $profile = $user->profile;

        $roleNames = $user->getRoleNames()->values();

        $isGlobalUser = $roleNames
            ->map(fn ($role) => strtolower((string) $role))
            ->contains(fn ($role) => in_array($role, [
                'super admin',
                'super_admin',
                'admin',
                'administrator',
            ], true));

        $warehouse = null;
        $warehouseLabel = '-';

        if ($profile?->warehouse_id) {
            $warehouse = DB::table('warehouses')
                ->where('id', $profile->warehouse_id)
                ->first(['id', 'code', 'name', 'address', 'phone']);

            $warehouseLabel = $warehouse?->name ?? '-';
        } elseif ($isGlobalUser) {
            $warehouse = (object) [
                'id' => null,
                'code' => 'ALL',
                'name' => 'Semua Gudang',
                'address' => null,
                'phone' => null,
            ];

            $warehouseLabel = 'Semua Gudang';
        }

        $inboundCount = DB::table('inbound_transactions')
            ->where('submitted_by', $user->id)
            ->count();

        $outboundCount = DB::table('outbound_transactions')
            ->where('submitted_by', $user->id)
            ->count();

        $stockQuery = DB::table('stock_balances')
            ->where('qty_on_hand', '>', 0);

        if ($profile?->warehouse_id) {
            $stockQuery->where('warehouse_id', $profile->warehouse_id);
        } elseif (! $isGlobalUser) {
            $warehouseIds = collect()
                ->merge(
                    DB::table('inbound_transactions')
                        ->where('submitted_by', $user->id)
                        ->pluck('warehouse_id')
                )
                ->merge(
                    DB::table('outbound_transactions')
                        ->where('submitted_by', $user->id)
                        ->pluck('warehouse_id')
                )
                ->filter()
                ->unique()
                ->values();

            if ($warehouseIds->isNotEmpty()) {
                $stockQuery->whereIn('warehouse_id', $warehouseIds);
            } else {
                $stockQuery->whereRaw('1 = 0');
            }
        }

        $stockCount = $stockQuery->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'avatar_url' => $user->avatar_url ? asset('storage/'.$user->avatar_url) : null,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $roleNames,
                ],
                'profile' => [
                    'username' => $profile?->username,
                    'phone' => $profile?->phone,
                    'employee_code' => $profile?->employee_code,
                    'position' => $profile?->position,
                    'address' => $profile?->address,
                    'is_active' => (bool) ($profile?->is_active ?? true),
                    'last_login_at' => $profile?->last_login_at,
                ],
                'warehouse' => $warehouse,
                'warehouse_label' => $warehouseLabel,
                'stats' => [
                    'inbound_count' => $inboundCount,
                    'outbound_count' => $outboundCount,
                    'stock_count' => $stockCount,
                ],
            ],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['new_password']),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }
}