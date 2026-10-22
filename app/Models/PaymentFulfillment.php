<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentFulfillment extends Model
{
    protected $fillable = ['payment_transaction_id','status','attempt_count','result'];
    public function transaction() { return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id'); }
}
