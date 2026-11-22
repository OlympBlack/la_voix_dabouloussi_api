<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmsLike extends Model
{
    protected $table = 'dms_likes';

    protected $fillable = ['contenu_id', 'visiteur'];
}
