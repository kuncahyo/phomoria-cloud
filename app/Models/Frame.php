<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Frame extends Model
{
    protected $fillable = [
        'name',
        'category',
        'image_path',
        'version',
        'sha256',
        'width',
        'height',
        'status',
    ];

    protected $casts = [
        'version' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function placements(): HasMany
    {
        return $this->hasMany(FramePlacement::class);
    }

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(
            Device::class,
            'device_frames'
        )->withTimestamps();
    }
}