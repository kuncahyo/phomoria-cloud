<?php

namespace App\Services;

use App\Models\PaymentAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DokuSnapService
{
    private const TOKEN_PATH = '/authorization/v1/access-token/b2b';
    private const QR_GENERATE_PATH = '/snap-adapter/b2b/v1.0/qr/qr-mpm-generate';
    private const QR_QUERY_PATH = '/snap-adapter/b2b/v1.0/qr/qr-mpm-query';
    private const QR_EXPIRE_PATH = '/snap-adapter/b2b/v1.0/qr/qr-expire';

    public function __construct(private PaymentAccount $account) {}

    private function baseUrl(): string
    {
        return $this->account->environment === 'production'
            ? 'https://api.doku.com'
            : 'https://api-sandbox.doku.com';
    }

    private function timestampUtc(): string { return now('UTC')->format('Y-m-d\\TH:i:s\\Z'); }
    private function timestampLocal(): string { return now('Asia/Jakarta')->format('Y-m-d\\TH:i:sP'); }

    private function signToken(string $timestamp): string
    {
        if (!$this->account->merchant_private_key) {
            throw new RuntimeException('Merchant private key belum dikonfigurasi.');
        }
        $stringToSign = $this->account->client_id . '|' . $timestamp;
        $privateKey = openssl_pkey_get_private($this->account->merchant_private_key);
        if ($privateKey === false) throw new RuntimeException('Merchant private key tidak dapat dibaca.');
        if (!openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Gagal membuat RSA-SHA256 signature DOKU.');
        }
        return base64_encode($signature);
    }

    private function token(): string
    {
        $timestamp = $this->timestampUtc();
        $response = Http::timeout(20)->acceptJson()->withHeaders([
            'X-CLIENT-KEY' => $this->account->client_id,
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $this->signToken($timestamp),
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl() . self::TOKEN_PATH, ['grantType' => 'client_credentials']);

        if (!$response->successful() || !$response->json('accessToken')) {
            throw new RuntimeException('DOKU token gagal: HTTP '.$response->status().' '.($response->json('responseMessage') ?? $response->body()));
        }
        return $response->json('accessToken');
    }

    private function externalId(): string
    {
        return now('Asia/Jakarta')->format('YmdHis') . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
    }

    private function signedRequest(string $method, string $path, array $body): array
    {
        $token = $this->token();
        $timestamp = $this->timestampLocal();
        $json = json_encode($body, JSON_UNESCAPED_SLASHES);
        if ($json === false) throw new RuntimeException('Gagal membuat request JSON DOKU.');
        $hash = strtolower(hash('sha256', $json));
        $stringToSign = strtoupper($method).':'.$path.':'.$token.':'.$hash.':'.$timestamp;
        $signature = base64_encode(hash_hmac('sha512', $stringToSign, $this->account->client_secret, true));
        $response = Http::timeout(30)->acceptJson()->withHeaders([
            'X-PARTNER-ID' => $this->account->client_id,
            'X-EXTERNAL-ID' => $this->externalId(),
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signature,
            'Authorization' => 'Bearer '.$token,
            'CHANNEL-ID' => $this->account->channel_id ?: 'H2H',
            'Content-Type' => 'application/json',
        ])->send($method, $this->baseUrl().$path, ['body' => $json]);

        if (!$response->successful()) {
            throw new RuntimeException('DOKU API gagal: HTTP '.$response->status().' '.($response->json('responseMessage') ?? $response->body()));
        }
        return $response->json() ?? [];
    }

    public function generate(string $partnerReferenceNo, int $amount, int $validMinutes = 15): array
    {
        if (!$this->account->merchant_id || !$this->account->terminal_id || !$this->account->postal_code) {
            throw new RuntimeException('Merchant ID, Terminal ID, dan Postal Code wajib dikonfigurasi.');
        }
        $validity = now('Asia/Jakarta')->addMinutes($validMinutes)->format('Y-m-d\\TH:i:sP');
        return $this->signedRequest('POST', self::QR_GENERATE_PATH, [
            'partnerReferenceNo' => $partnerReferenceNo,
            'amount' => ['value' => number_format($amount, 2, '.', ''), 'currency' => $this->account->currency],
            'merchantId' => $this->account->merchant_id,
            'terminalId' => $this->account->terminal_id,
            'validityPeriod' => $validity,
            'additionalInfo' => ['postalCode' => $this->account->postal_code, 'feeType' => '1'],
        ]);
    }

    public function query(string $reference): array
    {
        return $this->signedRequest('POST', self::QR_QUERY_PATH, [
            'originalReferenceNo' => $reference,
            'originalPartnerReferenceNo' => $reference,
            'merchantId' => $this->account->merchant_id,
            'serviceCode' => '47',
        ]);
    }

    public function expire(string $reference): array
    {
        return $this->signedRequest('POST', self::QR_EXPIRE_PATH, [
            'partnerReferenceNo' => $reference,
            'referenceNo' => $reference,
            'merchantId' => $this->account->merchant_id,
            'reason' => 'Phomoria cancellation',
        ]);
    }
}
