<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    protected $fillable = [
        'titre', 'slug', 'description', 'rubrique', 'couverture', 'date_album', 'statut',
    ];

    protected $casts = ['date_album' => 'date:Y-m-d'];

    public function photos()
    {
        return $this->hasMany(AlbumPhoto::class)->orderBy('ordre');
    }

    public function getUrlCouvertureAttribute(): ?string
    {
        return $this->couverture ? asset('storage/'.$this->couverture) : null;
    }
}
