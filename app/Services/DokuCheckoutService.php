<?php

namespace App\Services;

use App\Models\PaymentAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DokuCheckoutService
{
    private const CREATE_PATH = '/checkout/v1/payment';

    public function __construct(private readonly PaymentAccount $account) {}

    private function baseUrl(): string
    {
        return $this->account->environment === 'production'
            ? 'https://api.doku.com'
            : 'https://api-sandbox.doku.com';
    }

    private function timestamp(): string
    {
        return now('UTC')->format('Y-m-d\TH:i:s\Z');
    }

    private function signature(string $requestId, string $timestamp, string $path, ?string $body): string
    {
        $parts = [
            'Client-Id:' . $this->account->client_id,
            'Request-Id:' . $requestId,
            'Request-Timestamp:' . $timestamp,
            'Request-Target:' . $path,
        ];
        if ($body !== null) {
            $parts[] = 'Digest:' . base64_encode(hash('sha256', $body, true));
        }
        $raw = implode("\n", $parts);
        return 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $raw, $this->account->client_secret, true));
    }

    /** Create a DOKU-hosted Checkout URL. Amount is integer IDR. */
    public function createPayment(string $invoice, int $amount, string $customerName, ?string $customerEmail = null): array
    {
        if (!$this->account->client_id || !$this->account->client_secret) {
            throw new RuntimeException('DOKU Client ID dan Secret Key belum dikonfigurasi.');
        }
        if ($amount < 1) throw new RuntimeException('Nominal pembayaran harus lebih dari nol.');

        $requestId = (string) Str::uuid();
        $timestamp = $this->timestamp();
        $payload = [
            'order' => [
                'amount' => $amount,
                'invoice_number' => $invoice,
                'currency' => 'IDR',
            ],
            'payment' => [
                'payment_due_date' => 15,
                'payment_method_types' => ['QRIS'],
            ],
            'customer' => array_filter([
                'name' => mb_substr($customerName ?: 'Phomoria Customer', 0, 255),
                'email' => $customerEmail ?: null,
                'country' => 'ID',
            ], static fn ($value) => $value !== null && $value !== ''),
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($body === false) throw new RuntimeException('Tidak dapat membentuk request JSON DOKU.');

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'Client-Id' => $this->account->client_id,
                'Request-Id' => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature' => $this->signature($requestId, $timestamp, self::CREATE_PATH, $body),
                'Content-Type' => 'application/json',
            ])
            ->withBody($body, 'application/json')
            ->post($this->baseUrl() . self::CREATE_PATH);

        $json = $response->json() ?? [];
        if (!$response->successful()) {
            $message = implode('; ', (array) ($json['error_messages'] ?? $json['message'] ?? []));
            throw new RuntimeException('DOKU Checkout menolak request (HTTP ' . $response->status() . '). ' . ($message ?: 'Periksa konfigurasi dan log aplikasi.'));
        }

        $checkout = data_get($json, 'response.payment.url');
        if (!is_string($checkout) || !str_starts_with($checkout, 'https://')) {
            throw new RuntimeException('Respons DOKU tidak berisi response.payment.url yang valid.');
        }

        return [
            'request_id' => $requestId,
            'checkout_url' => $checkout,
            'token_id' => data_get($json, 'response.payment.token_id'),
            'expired_date' => data_get($json, 'response.payment.expired_date'),
            'raw' => $json,
        ];
    }

    /** Check Checkout transaction status using the non-SNAP Check Status API. */
    public function checkStatus(string $invoice): array
    {
        $path = '/orders/v1/status/' . rawurlencode($invoice);
        $requestId = (string) Str::uuid();
        $timestamp = $this->timestamp();
        $signature = $this->signature($requestId, $timestamp, $path, null);

        $response = Http::timeout(20)->acceptJson()->withHeaders([
            'Client-Id' => $this->account->client_id,
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => $signature,
        ])->get($this->baseUrl() . $path);

        $json = $response->json() ?? [];
        if (!$response->successful()) {
            throw new RuntimeException('DOKU Check Status gagal (HTTP ' . $response->status() . ').');
        }
        return $json;
    }

    public static function verifyNotification(PaymentAccount $account, string $clientId, string $requestId, string $timestamp, string $target, string $rawBody, string $providedSignature): bool
    {
        if (!hash_equals((string) $account->client_id, $clientId)) return false;
        $digest = base64_encode(hash('sha256', $rawBody, true));
        $component = implode("\n", [
            'Client-Id:' . $clientId,
            'Request-Id:' . $requestId,
            'Request-Timestamp:' . $timestamp,
            'Request-Target:' . $target,
            'Digest:' . $digest,
        ]);
        $expected = 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $component, $account->client_secret, true));
        return hash_equals($expected, $providedSignature);
    }
}
