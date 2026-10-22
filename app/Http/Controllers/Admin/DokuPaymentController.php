<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\PaymentAccount;
use App\Models\PaymentTransaction;
use App\Services\DokuSnapService;
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
        return view('admin.doku.index', [
            'account' => $this->account($request),
            'devices' => $request->user()->devices()->orderBy('computer_name')->get(),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'environment' => ['required','in:sandbox,production'],
            'client_id' => ['required','string','max:128'],
            'client_secret' => ['required','string','max:5000'],
            'merchant_private_key' => ['required','string','max:10000'],
            'doku_public_key' => ['nullable','string','max:10000'],
            'merchant_public_key' => ['nullable','string','max:10000'],
            'merchant_id' => ['required','string','max:64'],
            'terminal_id' => ['required','string','max:32'],
            'postal_code' => ['required','string','max:16'],
        ]);

        $account = $this->account($request) ?? new PaymentAccount(['user_id' => $request->user()->id, 'provider' => 'doku']);
        $account->fill($data);
        $account->channel_id = 'H2H';
        $account->currency = 'IDR';
        $account->is_active = true;
        $account->save();

        return back()->with('success', 'Konfigurasi DOKU berhasil disimpan terenkripsi.');
    }

    public function testQr(Request $request)
    {
        $data = $request->validate([
            'device_id' => ['required','integer'],
        ]);
        $device = $request->user()->devices()->whereKey($data['device_id'])->firstOrFail();
        if (!$device->session_price || $device->session_price < 1) {
            return back()->withErrors(['device_id' => 'Harga sesi perangkat belum dikonfigurasi.']);
        }
        $account = $this->account($request);
        if (!$account || !$account->is_active) return back()->withErrors(['device_id' => 'Akun DOKU belum dikonfigurasi.']);

        $reference = 'PHO-' . strtoupper(Str::random(20));
        try {
            $response = app(DokuSnapService::class, ['account' => $account])->generate($reference, (int) $device->session_price);
            $transaction = DB::transaction(function () use ($request, $device, $account, $reference, $response) {
                $transaction = PaymentTransaction::create([
                    'user_id' => $request->user()->id,
                    'device_id' => $device->id,
                    'payment_account_id' => $account->id,
                    'client_session_id' => (string) Str::uuid(),
                    'merchant_reference' => $reference,
                    'provider_reference' => $response['referenceNo'] ?? $reference,
                    'amount' => (int) $device->session_price,
                    'currency' => 'IDR',
                    'description' => 'Phomoria test QRIS',
                    'status' => 'PENDING',
                    'qr_content' => $response['qrContent'] ?? null,
                    'expires_at' => now()->addMinutes(15),
                    'provider_response' => $response,
                ]);
                $transaction->events()->create(['source' => 'doku_generate', 'status' => 'PENDING', 'metadata' => $response]);
                return $transaction;
            });
            return redirect()->route('admin.doku.test', $transaction->public_id);
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['device_id' => $e->getMessage()]);
        }
    }

    public function showTest(Request $request, string $publicId)
    {
        $transaction = PaymentTransaction::query()->where('public_id', $publicId)->where('user_id', $request->user()->id)->firstOrFail();
        return view('admin.doku.test', compact('transaction'));
    }

    public function status(Request $request, string $publicId)
    {
        $transaction = PaymentTransaction::query()->where('public_id', $publicId)->where('user_id', $request->user()->id)->firstOrFail();
        if ($transaction->status === 'PENDING' && $transaction->provider_reference) {
            try {
                $response = app(DokuSnapService::class, ['account' => $transaction->paymentAccount])->query($transaction->provider_reference);
                $status = $response['latestTransactionStatus'] ?? null;
                if ($status === '00') {
                    DB::transaction(function () use ($transaction, $response) {
                        $locked = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->first();
                        if ($locked->status !== 'PAID') {
                            $locked->status = 'PAID';
                            $locked->paid_at = now();
                            $locked->provider_response = $response;
                            $locked->save();
                            $locked->events()->create(['source' => 'doku_query', 'status' => 'PAID', 'metadata' => $response]);
                        }
                    });
                }
            } catch (Throwable $e) {
                report($e);
            }
        }
        $transaction->refresh();
        return response()->json(['status' => $transaction->status, 'paid_at' => optional($transaction->paid_at)->toIso8601String()]);
    }
}
