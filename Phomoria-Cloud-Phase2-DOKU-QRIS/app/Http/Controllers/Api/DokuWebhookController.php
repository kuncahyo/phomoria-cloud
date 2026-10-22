<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentAccount;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DokuWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $raw = $request->getContent();
        $headers = $request->headers->all();
        $clientId = $request->header('Client-Id');
        $requestId = $request->header('Request-Id');
        $requestTimestamp = $request->header('Request-Timestamp');
        $signature = $request->header('Signature');
        $target = $request->getPathInfo();

        $account = PaymentAccount::query()->where('provider', 'doku')->where('client_id', $clientId)->where('is_active', true)->first();
        $digest = base64_encode(hash('sha256', $raw, true));
        $component = "Client-Id:{$clientId}\nRequest-Id:{$requestId}\nRequest-Timestamp:{$requestTimestamp}\nRequest-Target:{$target}\nDigest:{$digest}";
        $expected = $account ? 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $component, $account->client_secret, true)) : null;
        $valid = $expected && $signature && hash_equals($expected, $signature);
        $payload = json_decode($raw, true) ?: [];
        $eventKey = $requestId ?: hash('sha256', $raw);

        $log = PaymentWebhookLog::firstOrCreate(['event_key' => $eventKey], [
            'payment_account_id' => $account?->id,
            'request_id' => $requestId,
            'signature_valid' => (bool) $valid,
            'processing_status' => $valid ? 'RECEIVED' : 'REJECTED_SIGNATURE',
            'headers' => array_map(fn($v) => is_array($v) ? $v : [$v], $headers),
            'payload' => $payload,
        ]);

        if (!$valid) return response()->json(['message' => 'Invalid signature'], 401);
        if (!$account) return response()->json(['message' => 'Unknown client'], 401);

        $reference = data_get($payload, 'order.invoice_number') ?: data_get($payload, 'payment.merchant_unique_reference');
        $status = strtoupper((string) data_get($payload, 'transaction.status'));
        $amount = (int) round((float) data_get($payload, 'order.amount', 0));

        $transaction = PaymentTransaction::query()->where('merchant_reference', $reference)->where('payment_account_id', $account->id)->first();
        if (!$transaction) {
            $log->update(['processing_status' => 'IGNORED_NO_TRANSACTION']);
            return response()->json(['message' => 'Acknowledged']);
        }

        DB::transaction(function () use ($transaction, $status, $amount, $payload, $eventKey, $log) {
            $locked = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->first();
            $locked->events()->create(['source' => 'doku_webhook', 'event_id' => $eventKey, 'status' => $status, 'metadata' => $payload]);
            if ($amount > 0 && $amount !== (int) $locked->amount) {
                $log->update(['processing_status' => 'REJECTED_AMOUNT']);
                return;
            }
            if ($status === 'SUCCESS' && $locked->status !== 'PAID') {
                $locked->status = 'PAID';
                $locked->paid_at = now();
                $locked->provider_response = $payload;
                $locked->save();
            } elseif ($status === 'FAILED' && $locked->status === 'PENDING') {
                $locked->status = 'FAILED';
                $locked->provider_response = $payload;
                $locked->save();
            }
            $log->update(['processing_status' => 'PROCESSED']);
        });

        return response()->json(['message' => 'Acknowledged']);
    }
}
