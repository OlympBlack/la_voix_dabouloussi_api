<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rayon extends Model
{
    protected $fillable = ['nom', 'taux'];

    protected $casts = ['taux' => 'float'];

    public function produits()
    {
        return $this->hasMany(Produit::class);
    }
}
