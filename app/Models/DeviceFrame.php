<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceFrame extends Model
{
    protected $fillable = [
        'device_id',
        'frame_id',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function frame(): BelongsTo
    {
        return $this->belongsTo(Frame::class);
    }
}