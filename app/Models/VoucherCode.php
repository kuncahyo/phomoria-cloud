<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherCode extends Model
{
    protected $fillable = ['code', 'status', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }
}
