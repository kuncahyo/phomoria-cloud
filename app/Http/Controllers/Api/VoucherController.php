<?php

namespace App\\Http\\Controllers\\Api;

use App\\Http\\Controllers\\Controller;
use App\\Models\\Device;
use App\\Models\\VoucherCode;
use App\\Models\\VoucherRedemption;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Str;

class VoucherController extends Controller
{
    /**
     * Validate a voucher and authorize exactly one upcoming photo session.
     *
     * A one-time voucher is changed from active to used in the same database
     * transaction that creates its session authorization. Secret vouchers
     * remain reusable and retain their status.
     */
    public function authorizeSession(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
            'device_uuid' => ['required', 'uuid'],
        ]);

        $user = $request->user();
        $plainToken = Str::random(64);

        $result = DB::transaction(function () use ($validated, $user, $plainToken) {
            $device = Device::query()
                ->where('device_uuid', $validated['device_uuid'])
                ->where('user_id', $user->id)
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if (!$device) {
                return ['error' => 'device'];
            }

            $voucher = VoucherCode::query()
                ->where('code', Str::upper(trim($validated['code'])))
                ->lockForUpdate()
                ->first();

            if (!$voucher || !in_array($voucher->status, ['active', 'secret'], true)) {
                return ['error' => 'voucher'];
            }

            if ($voucher->expires_at && $voucher->expires_at->isPast()) {
                return ['error' => 'voucher'];
            }

            $voucher->redemptions()->create([
                'user_id' => $user->id,
                'device_id' => $device->id,
                'session_token_hash' => hash('sha256', $plainToken),
                'status' => 'authorized',
                'expires_at' => now()->addHours(4),
            ]);

            if ($voucher->status === 'active') {
                $voucher->status = 'used';
                $voucher->save();
            }

            return ['device_id' => $device->id, 'voucher_status' => $voucher->status];
        }, 3);

        if (isset($result['error'])) {
            if ($result['error'] === 'device') {
                return response()->json([
                    'success' => false,
                    'message' => 'Device tidak aktif atau bukan milik akun ini.',
                ], 403);
            }

            return response()->json([
                'success' => false,
                'message' => 'Kode voucher salah atau sudah tidak aktif.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil digunakan. Sesi foto diizinkan.',
            'session_authorization_token' => $plainToken,
            'expires_at' => now()->addHours(4)->toISOString(),
        ]);
    }
}
