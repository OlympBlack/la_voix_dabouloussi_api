<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'medias';

    protected $fillable = [
        'user_id', 'nom', 'chemin', 'type', 'mime', 'taille', 'alt', 'rubrique',
    ];

    protected $casts = ['taille' => 'integer'];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->chemin);
    }
}
