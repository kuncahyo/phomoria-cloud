<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookLog extends Model
{
    protected $fillable = ['payment_account_id','request_id','event_key','signature_valid','processing_status','headers','payload','error_message'];
    protected function casts(): array { return ['signature_valid' => 'boolean', 'headers' => 'array', 'payload' => 'array']; }
}
