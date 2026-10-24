<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentAccount;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookLog;
use App\Services\DokuCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DokuWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $raw = $request->getContent();
        $clientId = (string) $request->header('Client-Id', '');
        $requestId = (string) $request->header('Request-Id', '');
        $timestamp = (string) $request->header('Request-Timestamp', '');
        $signature = (string) $request->header('Signature', '');
        $target = $request->getPathInfo();
        $account = PaymentAccount::query()->where('provider', 'doku')->where('client_id', $clientId)->where('is_active', true)->first();

        if (!$account || !$requestId || !$timestamp || !$signature || !DokuCheckoutService::verifyNotification($account, $clientId, $requestId, $timestamp, $target, $raw, $signature)) {
            // Do not reserve a real Request-Id for an unauthenticated request (poisoning/replay risk).
            PaymentWebhookLog::create([
                'event_key' => 'invalid-' . hash('sha256', $requestId . $raw . Str::random(8)),
                'request_id' => $requestId ?: null,
                'payment_account_id' => $account?->id,
                'signature_valid' => false,
                'processing_status' => 'REJECTED_SIGNATURE',
                'headers' => $request->headers->all(),
                'payload' => json_decode($raw, true),
            ]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $eventKey = 'doku-' . $requestId;
        $payload = json_decode($raw, true) ?: [];
        try {
            $log = PaymentWebhookLog::firstOrCreate(['event_key' => $eventKey], [
                'payment_account_id' => $account->id,
                'request_id' => $requestId,
                'signature_valid' => true,
                'processing_status' => 'RECEIVED',
                'headers' => $request->headers->all(),
                'payload' => $payload,
            ]);
        } catch (Throwable $e) {
            // Unique event key means an already accepted notification is a duplicate.
            report($e);
            return response()->json(['message' => 'Acknowledged']);
        }
        if (!$log->wasRecentlyCreated) return response()->json(['message' => 'Acknowledged']);

        $invoice = (string) data_get($payload, 'order.invoice_number', '');
        $status = strtoupper((string) data_get($payload, 'transaction.status', ''));
        $amount = (int) round((float) data_get($payload, 'order.amount', 0));
        $transaction = PaymentTransaction::query()->where('merchant_reference', $invoice)
            ->where('payment_account_id', $account->id)->first();

        if (!$transaction) {
            $log->update(['processing_status' => 'IGNORED_NO_TRANSACTION']);
            return response()->json(['message' => 'Acknowledged']);
        }

        DB::transaction(function () use ($transaction, $status, $amount, $payload, $requestId, $log) {
            $locked = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if ($amount !== (int) $locked->amount) {
                $log->update(['processing_status' => 'REJECTED_AMOUNT', 'error_message' => 'Webhook amount does not match transaction amount.']);
                return;
            }
            $locked->events()->create(['source' => 'doku_checkout_webhook', 'event_id' => $requestId, 'status' => $status, 'metadata' => $payload]);
            if ($status === 'SUCCESS' && $locked->status !== 'PAID') {
                $locked->status = 'PAID';
                $locked->paid_at = now();
                $locked->provider_response = $payload;
                $locked->save();
            }
            // DOKU Checkout explicitly requires FAILED to be ignored so customers can retry another method.
            $log->update(['processing_status' => $status === 'FAILED' ? 'IGNORED_CHECKOUT_FAILED' : 'PROCESSED']);
        });

        return response()->json(['message' => 'Acknowledged']);
    }
}
