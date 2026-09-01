<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoSession extends Model
{

    protected $fillable = [

        'user_id',
        'device_id',
        'session_code',
        'frame_name',
        'photo_count',
        'status',
        'expired_at'

    ];

    protected $casts = [

        'expired_at' => 'datetime'

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