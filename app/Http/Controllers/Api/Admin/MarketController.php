<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Models\Rayon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function rayons(): JsonResponse
    {
        return response()->json(['data' => Rayon::withCount('produits')->get()]);
    }

    public function produits(): JsonResponse
    {
        return response()->json(['data' => Produit::with('rayon')->orderByDesc('id')->get()]);
    }

    public function storeProduit(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'rayon_id' => ['required', 'exists:rayons,id'],
            'prix' => ['required', 'numeric', 'min:0'],
            'designation' => ['nullable', 'string', 'max:180'],
        ]);

        return response()->json(['message' => 'Produit ajouté.', 'data' => Produit::create($donnees)], 201);
    }

    public function destroyProduit(Produit $produit): JsonResponse
    {
        $produit->delete();

        return response()->json(['message' => 'Produit supprimé.']);
    }
}
