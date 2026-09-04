<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FramePlacement extends Model
{
    protected $fillable = [
        'frame_id',
        'slot',
        'x',
        'y',
        'width',
        'height',
        'rotation',
    ];

    protected $casts = [
        'slot' => 'integer',
        'x' => 'integer',
        'y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'rotation' => 'float',
    ];

    public function frame(): BelongsTo
    {
        return $this->belongsTo(Frame::class);
    }
}