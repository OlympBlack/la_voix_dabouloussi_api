<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Livebet extends Model
{
    protected $fillable = ['chiffre', 'prix_ht', 'resultat', 'message'];

    protected $casts = ['prix_ht' => 'float', 'chiffre' => 'integer'];
}
