<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoSession extends Model
{

    protected $fillable = [

        'user_id',
        'device_id',
        'session_code',
        'client_session_id',
        'frame_name',
        'photo_count',
        'expected_assets',
        'status',
        'expired_at'

    ];

    protected $casts = [

        'expired_at' => 'datetime',
        'expected_assets' => 'array'

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

}