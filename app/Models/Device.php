<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{

    protected $fillable = [

        'user_id',

        'device_uuid',

        'computer_name',

        'windows_user',

        'operating_system',

        'java_version',

        'app_version',

        'status',

        'last_online'

    ];

    public function user()
    {

        return $this->belongsTo(User::class);

    }

    public function photoSessions()
    {
        return $this->hasMany(PhotoSession::class);
    }

    public function frames()
    {
        return $this->belongsToMany(
            Frame::class,
            'device_frames'
        )->withTimestamps();
    }

}