<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlbumPhoto extends Model
{
    protected $fillable = ['album_id', 'image', 'legende', 'ordre'];

    protected $appends = ['url'];

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->image);
    }
}
