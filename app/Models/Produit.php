<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    protected $fillable = ['rayon_id', 'prix', 'designation'];

    protected $casts = ['prix' => 'float'];

    public function rayon()
    {
        return $this->belongsTo(Rayon::class);
    }
}
