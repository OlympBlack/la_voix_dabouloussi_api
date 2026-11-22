<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contenu extends Model
{
    protected $table = 'contenus';

    protected $fillable = [
        'user_id', 'categorie_id', 'rubrique', 'titre', 'slug', 'resume', 'corps',
        'auteur', 'date_publication', 'statut', 'image', 'document', 'document_nom',
        'meta', 'a_la_une', 'vues',
    ];

    protected $casts = [
        'meta' => 'array',
        'date_publication' => 'date:Y-m-d',
        'a_la_une' => 'boolean',
        'vues' => 'integer',
    ];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function auteurUtilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublie($query)
    {
        return $query->where('statut', 'publie');
    }

    public function scopeRubrique($query, string $rubrique)
    {
        return $query->where('rubrique', $rubrique);
    }

    public function getUrlImageAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    public function getUrlDocumentAttribute(): ?string
    {
        return $this->document ? asset('storage/'.$this->document) : null;
    }

    protected static function booted(): void
    {
        static::deleting(function (Contenu $contenu): void {
            $contenu->dmsLikes()->delete();
        });
    }

    public function dmsLikes()
    {
        return $this->hasMany(DmsLike::class);
    }
}
