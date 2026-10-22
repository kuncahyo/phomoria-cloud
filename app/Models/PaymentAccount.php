<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAccount extends Model
{
    protected $fillable = [
        'user_id','provider','name','environment','client_id','client_secret',
        'merchant_private_key','doku_public_key','merchant_public_key','merchant_id',
        'terminal_id','postal_code','channel_id','currency','is_active',
    ];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
            'merchant_private_key' => 'encrypted',
            'doku_public_key' => 'encrypted',
            'merchant_public_key' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function transactions() { return $this->hasMany(PaymentTransaction::class); }
}
