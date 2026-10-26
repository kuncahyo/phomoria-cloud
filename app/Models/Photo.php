<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $fillable = [
        'photo_session_id',
        'filename',
        'original_name',
        'is_result',
        'is_live_photo',
        'sort_order',
        'file_size',
    ];

    protected $casts = [
        'is_result' => 'boolean',
        'is_live_photo' => 'boolean',
    ];

    protected $appends = ['url'];

    public function session()
    {
        return $this->belongsTo(PhotoSession::class, 'photo_session_id');
    }

    public function getUrlAttribute()
    {
        $session = $this->session;

        return asset(
            'storage/sessions/'
            . $session->created_at->format('Y') . '/'
            . $session->created_at->format('m') . '/'
            . $session->created_at->format('d') . '/'
            . $session->session_code . '/'
            . $this->filename
        );
    }
}
