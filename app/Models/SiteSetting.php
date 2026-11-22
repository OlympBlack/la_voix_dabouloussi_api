<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = ['cle', 'libelle', 'valeur', 'groupe'];

    public static function valeur(string $cle, string $defaut = ''): string
    {
        return static::where('cle', $cle)->value('valeur') ?? $defaut;
    }
}
