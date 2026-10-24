<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'public_id', 'user_id', 'device_id', 'payment_account_id', 'client_session_id',
        'merchant_reference', 'provider_reference', 'provider_request_id', 'amount', 'currency',
        'description', 'status', 'qr_content', 'checkout_url', 'doku_token_id', 'expires_at',
        'paid_at', 'cancelled_at', 'provider_response',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'expires_at' => 'datetime', 'paid_at' => 'datetime', 'cancelled_at' => 'datetime', 'provider_response' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $transaction) { $transaction->public_id ??= (string) Str::uuid(); });
    }

    public function user() { return $this->belongsTo(User::class); }
    public function device() { return $this->belongsTo(Device::class); }
    public function paymentAccount() { return $this->belongsTo(PaymentAccount::class); }
    public function events() { return $this->hasMany(PaymentTransactionEvent::class); }
}
