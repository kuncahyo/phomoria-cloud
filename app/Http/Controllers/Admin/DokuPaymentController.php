<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentAccount;
use App\Models\PaymentTransaction;
use App\Services\DokuCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DokuPaymentController extends Controller
{
    private function account(Request $request): ?PaymentAccount
    {
        return PaymentAccount::query()->where('user_id', $request->user()->id)->where('provider', 'doku')->first();
    }

    public function index(Request $request)
    {
        return view('admin.doku.index', ['account' => $this->account($request), 'devices' => $request->user()->devices()->orderBy('computer_name')->get()]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'environment' => ['required', 'in:sandbox,production'],
            'client_id' => ['required', 'string', 'max:128'],
            'client_secret' => ['nullable', 'string', 'max:5000'],
        ]);
        $account = $this->account($request) ?? new PaymentAccount(['user_id' => $request->user()->id, 'provider' => 'doku']);
        $secret = $data['client_secret'] ?? '';
        unset($data['client_secret']);
        if (!$account->exists && $secret === '') return back()->withErrors(['client_secret' => 'Secret Key wajib diisi untuk konfigurasi pertama.'])->withInput($request->except('client_secret'));
        $account->fill($data);
        if ($secret !== '') $account->client_secret = $secret;
        $account->is_active = true;
        $account->currency = 'IDR';
        $account->save();
        return back()->with('success', 'Konfigurasi DOKU Checkout tersimpan. Secret Key terenkripsi dan tidak ditampilkan kembali.');
    }

    public function testGenerate(Request $request)
    {
        $data = $request->validate(['device_id' => ['required', 'integer']]);
        $device = $request->user()->devices()->whereKey($data['device_id'])->firstOrFail();
        if (!$device->session_price || $device->session_price < 1) return back()->withErrors(['device_id' => 'Harga sesi perangkat belum ditentukan.']);
        $account = $this->account($request);
        if (!$account || !$account->is_active || !$account->client_secret) return back()->withErrors(['device_id' => 'Konfigurasi DOKU Checkout belum lengkap.']);

        $invoice = 'PHOTEST' . now()->format('ymdHis') . strtoupper(Str::random(6));
        $transaction = PaymentTransaction::create([
            'user_id' => $request->user()->id,
            'device_id' => $device->id,
            'payment_account_id' => $account->id,
            'client_session_id' => (string) Str::uuid(),
            'merchant_reference' => $invoice,
            'amount' => (int) $device->session_price,
            'currency' => 'IDR',
            'description' => 'Phomoria DOKU Checkout Sandbox Test',
            'status' => 'CREATING',
            'expires_at' => now()->addMinutes(15),
        ]);
        try {
            $result = app(DokuCheckoutService::class, ['account' => $account])->createPayment($invoice, (int) $transaction->amount, $request->user()->name ?: 'Phomoria Customer', $request->user()->email);
            DB::transaction(function () use ($transaction, $result) {
                $tx = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
                $tx->status = 'PENDING';
                $tx->checkout_url = $result['checkout_url'];
                $tx->doku_token_id = $result['token_id'];
                $tx->provider_request_id = $result['request_id'];
                $tx->provider_reference = $result['token_id'];
                $tx->provider_response = $result['raw'];
                $tx->save();
                $tx->events()->create(['source' => 'doku_checkout_test', 'status' => 'PENDING', 'metadata' => $result['raw']]);
            });
            return redirect()->route('admin.doku.test', $transaction->public_id);
        } catch (Throwable $e) {
            report($e);
            $transaction->update(['status' => 'CREATION_UNKNOWN']);
            return back()->withErrors(['device_id' => 'Gagal memastikan hasil pembuatan Checkout. Periksa log aplikasi dan status DOKU sebelum mencoba ulang.']);
        }
    }

    public function showTest(Request $request, string $publicId)
    {
        $transaction = PaymentTransaction::query()->where('public_id', $publicId)->where('user_id', $request->user()->id)->firstOrFail();
        return view('admin.doku.test', compact('transaction'));
    }

    public function status(Request $request, string $publicId)
    {
        $transaction = PaymentTransaction::query()->where('public_id', $publicId)->where('user_id', $request->user()->id)->with('paymentAccount')->firstOrFail();
        if ($transaction->status === 'PENDING' && $transaction->created_at->lte(now()->subSeconds(60))) {
            try {
                $result = app(DokuCheckoutService::class, ['account' => $transaction->paymentAccount])->checkStatus($transaction->merchant_reference);
                $status = strtoupper((string) data_get($result, 'response.transaction.status', data_get($result, 'transaction.status', '')));
                if ($status === 'SUCCESS') {
                    DB::transaction(function () use ($transaction, $result) {
                        $tx = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
                        if ($tx->status === 'PENDING') {
                            $tx->status = 'PAID'; $tx->paid_at = now(); $tx->provider_response = $result; $tx->save();
                            $tx->events()->create(['source' => 'doku_checkout_status', 'status' => 'PAID', 'metadata' => $result]);
                        }
                    });
                }
            } catch (Throwable $e) { report($e); }
        }
        $transaction->refresh();
        return response()->json(['status' => $transaction->status, 'paid_at' => $transaction->paid_at?->toIso8601String()]);
    }
}
