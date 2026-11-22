<?php

namespace App\Support;

use App\Models\Contenu;
use Illuminate\Support\Str;

class ApiData
{
    public static function contenu(Contenu $contenu): array
    {
        return [
            'id' => $contenu->id,
            'rubrique' => $contenu->rubrique,
            'titre' => $contenu->titre,
            'slug' => $contenu->slug,
            'resume' => $contenu->resume,
            'corps' => $contenu->corps,
            'auteur' => $contenu->auteur,
            'date_publication' => $contenu->date_publication?->format('Y-m-d'),
            'statut' => $contenu->statut,
            'image' => $contenu->url_image,
            'document' => $contenu->url_document,
            'document_nom' => $contenu->document_nom,
            'meta' => $contenu->meta ?? [],
            'a_la_une' => (bool) $contenu->a_la_une,
            'vues' => $contenu->vues,
            'likes' => $contenu->rubrique === 'dms' ? $contenu->dmsLikes()->count() : null,
            'categorie' => $contenu->categorie ? [
                'id' => $contenu->categorie->id,
                'nom' => $contenu->categorie->nom,
                'slug' => $contenu->categorie->slug,
            ] : null,
            'created_at' => $contenu->created_at?->toISOString(),
        ];
    }

    public static function user($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'actif' => (bool) $user->actif,
            'permissions' => $user->role === 'admin'
                ? ['*']
                : $user->permissions()->pluck('name')->values()->all(),
        ];
    }

    public static function slug(string $titre, string $rubrique): string
    {
        $base = Str::slug($titre) ?: Str::lower(Str::random(8));
        $slug = $base;
        $suffixe = 2;

        while (Contenu::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffixe++;
        }

        return $slug;
    }
}
