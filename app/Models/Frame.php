<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Frame extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'category',
        'printer_profile',
        'image_path',
        'version',
        'sha256',
        'width',
        'height',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'version' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(FramePlacement::class)->orderBy('slot');
    }

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(
            Device::class,
            'device_frames'
        )->withTimestamps();
    }
}
