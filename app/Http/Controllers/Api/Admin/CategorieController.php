<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategorieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $donnees = $request->validate(['rubrique' => ['nullable', 'string', 'max:40']]);
        $query = Categorie::withCount('contenus');

        if (! empty($donnees['rubrique'])) {
            $query->where('rubrique', $donnees['rubrique']);
        }

        return response()->json(['data' => $query->orderBy('rubrique')->orderBy('ordre')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $donnees = $this->valider($request);
        $donnees['slug'] = $this->slug($donnees['nom']);

        return response()->json(['message' => 'Catégorie créée.', 'data' => Categorie::create($donnees)], 201);
    }

    public function update(Request $request, Categorie $categorie): JsonResponse
    {
        $donnees = $this->valider($request, $categorie);
        $donnees['slug'] = $this->slug($donnees['nom'], $categorie->id);
        $categorie->update($donnees);

        return response()->json(['message' => 'Catégorie mise à jour.', 'data' => $categorie]);
    }

    public function destroy(Categorie $categorie): JsonResponse
    {
        $categorie->delete();

        return response()->json(['message' => 'Catégorie supprimée.']);
    }

    private function valider(Request $request, ?Categorie $categorie = null): array
    {
        return $request->validate([
            'rubrique' => ['required', 'string', 'max:40'],
            'nom' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:600'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function slug(string $nom, ?int $ignore = null): string
    {
        $base = Str::slug($nom) ?: 'categorie';
        $slug = $base;
        $suffixe = 2;

        while (Categorie::where('slug', $slug)->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists()) {
            $slug = $base.'-'.$suffixe++;
        }

        return $slug;
    }
}
