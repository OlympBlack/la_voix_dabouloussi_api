<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    protected $fillable = ['rubrique', 'nom', 'slug', 'description', 'ordre'];

    public function contenus()
    {
        return $this->hasMany(Contenu::class);
    }
}
