<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoSessionAsset extends Model
{
    protected $fillable = [
        'photo_session_id', 'asset_key', 'asset_type', 'filename',
        'original_name', 'sort_order', 'expected_size', 'file_size', 'status',
    ];

    public function session()
    {
        return $this->belongsTo(PhotoSession::class, 'photo_session_id');
    }
}
