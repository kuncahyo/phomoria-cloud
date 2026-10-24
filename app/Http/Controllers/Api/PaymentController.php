<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentAccount;
use App\Models\PaymentTransaction;
use App\Services\DokuCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Throwable;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'device_uuid' => ['required', 'string', 'max:100'],
            'client_session_id' => ['required', 'uuid'],
        ]);

        $user = $request->user();
        $device = $user->devices()->where('device_uuid', $data['device_uuid'])->first();
        if (!$device) return response()->json(['success' => false, 'message' => 'Device tidak ditemukan.'], 404);
        if ($device->status !== 'ACTIVE') return response()->json(['success' => false, 'message' => 'Device tidak aktif.'], 422);
        if (!$device->session_price || $device->session_price < 1) return response()->json(['success' => false, 'message' => 'Harga sesi perangkat belum dikonfigurasi.'], 422);

        $existing = PaymentTransaction::query()
            ->where('user_id', $user->id)->where('device_id', $device->id)
            ->where('client_session_id', $data['client_session_id'])->latest('id')->first();
        if ($existing) {
            if (in_array($existing->status, ['PENDING', 'PAID', 'CREATING', 'CREATION_UNKNOWN'], true)) {
                return $this->present($existing);
            }
            return response()->json(['success' => false, 'message' => 'Sesi ini sudah memiliki transaksi pembayaran dengan status ' . $existing->status . '. Buat client_session_id baru hanya untuk sesi foto yang benar-benar baru.', 'transaction_id' => $existing->public_id], 409);
        }

        $account = PaymentAccount::query()->where('user_id', $user->id)
            ->where('provider', 'doku')->where('is_active', true)->first();
        if (!$account) return response()->json(['success' => false, 'message' => 'Akun DOKU belum dikonfigurasi.'], 422);

        // Persist an internal transaction first. This prevents an untracked provider payment
        // if a later database write fails. The unique constraint in the follow-up hardening
        // migration will close the concurrent-request race; until then the lookup is best-effort.
        $invoice = 'PHO' . now()->format('ymdHis') . strtoupper(Str::random(8));
        try {
            $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'payment_account_id' => $account->id,
            'client_session_id' => $data['client_session_id'],
            'merchant_reference' => $invoice,
            'amount' => (int) $device->session_price,
            'currency' => 'IDR',
            'description' => 'Phomoria Photo Session',
            'status' => 'CREATING',
            'expires_at' => now()->addMinutes(15),
            ]);
        } catch (QueryException $e) {
            $raced = PaymentTransaction::query()->where('user_id', $user->id)->where('device_id', $device->id)->where('client_session_id', $data['client_session_id'])->first();
            if ($raced) return $this->present($raced);
            throw $e;
        }

        try {
            $result = app(DokuCheckoutService::class, ['account' => $account])->createPayment(
                $invoice,
                (int) $transaction->amount,
                $user->name ?: 'Phomoria Customer',
                $user->email
            );

            DB::transaction(function () use ($transaction, $result) {
                $locked = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
                $locked->status = 'PENDING';
                $locked->checkout_url = $result['checkout_url'];
                $locked->doku_token_id = $result['token_id'];
                $locked->provider_request_id = $result['request_id'];
                $locked->provider_reference = $result['token_id'];
                $locked->provider_response = $result['raw'];
                if ($result['expired_date']) {
                    try { $locked->expires_at = \Carbon\Carbon::createFromFormat('YmdHis', $result['expired_date'], 'Asia/Jakarta')->utc(); }
                    catch (Throwable) { /* Keep the locally calculated expiry. */ }
                }
                $locked->save();
                $locked->events()->create(['source' => 'doku_checkout_create', 'status' => 'PENDING', 'metadata' => $result['raw']]);
            });

            return $this->present($transaction->fresh(), 201);
        } catch (Throwable $e) {
            report($e);
            // Do not blindly retry: a timeout may happen after DOKU created the hosted page.
            $transaction->update(['status' => 'CREATION_UNKNOWN']);
            return response()->json([
                'success' => false,
                'message' => 'Status pembuatan pembayaran belum dapat dipastikan. Jangan membuat sesi baru; lakukan pemeriksaan transaksi atau hubungi admin.',
                'transaction_id' => $transaction->public_id,
            ], 502);
        }
    }

    public function show(Request $request, string $publicId)
    {
        $transaction = PaymentTransaction::query()->where('public_id', $publicId)
            ->where('user_id', $request->user()->id)->firstOrFail();
        return $this->present($transaction);
    }

    public function refresh(Request $request, string $publicId)
    {
        $transaction = PaymentTransaction::query()->where('public_id', $publicId)
            ->where('user_id', $request->user()->id)->with('paymentAccount')->firstOrFail();
        if ($transaction->status !== 'PENDING' || $transaction->created_at->gt(now()->subSeconds(60))) {
            return $this->present($transaction);
        }

        try {
            $result = app(DokuCheckoutService::class, ['account' => $transaction->paymentAccount])
                ->checkStatus($transaction->merchant_reference);
            $providerStatus = strtoupper((string) data_get($result, 'response.transaction.status', data_get($result, 'transaction.status', '')));
            if ($providerStatus === 'SUCCESS') {
                DB::transaction(function () use ($transaction, $result) {
                    $locked = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
                    if ($locked->status === 'PENDING') {
                        $locked->status = 'PAID';
                        $locked->paid_at = now();
                        $locked->provider_response = $result;
                        $locked->save();
                        $locked->events()->create(['source' => 'doku_checkout_status', 'status' => 'PAID', 'metadata' => $result]);
                    }
                });
            }
            // For Checkout, FAILED is deliberately not terminal: the payer may choose another method.
        } catch (Throwable $e) {
            report($e);
        }
        return $this->present($transaction->fresh());
    }

    private function present(PaymentTransaction $transaction, int $httpStatus = 200)
    {
        return response()->json(['success' => true, 'data' => [
            'transaction_id' => $transaction->public_id,
            'merchant_reference' => $transaction->merchant_reference,
            'amount' => (int) $transaction->amount,
            'currency' => $transaction->currency,
            'status' => $transaction->status,
            'checkout_url' => $transaction->checkout_url,
            'expires_at' => $transaction->expires_at?->toIso8601String(),
            'paid_at' => $transaction->paid_at?->toIso8601String(),
        ]], $httpStatus);
    }
}
