<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransactionEvent extends Model
{
    protected $fillable = ['payment_transaction_id','source','event_id','status','metadata'];
    protected function casts(): array { return ['metadata' => 'array']; }
    public function transaction() { return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id'); }
}
