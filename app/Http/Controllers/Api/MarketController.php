<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Livebet;
use App\Models\Produit;
use App\Models\Rayon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function rayons(): JsonResponse
    {
        $rayons = Rayon::withCount('produits')->with(['produits' => function ($query): void {
            $query->select('id', 'rayon_id', 'prix', 'designation');
        }])->orderBy('nom')->get()->map(function (Rayon $rayon): array {
            $prix = $rayon->produits->pluck('prix');

            return [
                'id' => $rayon->id,
                'nom' => $rayon->nom,
                'taux' => $rayon->taux,
                'produits_count' => $rayon->produits_count,
                'prix_max' => $prix->max(),
                'prix_min' => $prix->min(),
                'total_ht' => round((float) $prix->sum(), 2),
                'produits' => $rayon->produits->values()->all(),
            ];
        });

        return response()->json(['data' => $rayons]);
    }

    public function produits(Request $request): JsonResponse
    {
        $donnees = $request->validate(['rayon' => ['nullable', 'string', 'max:100']]);
        $query = Produit::with('rayon');

        if (! empty($donnees['rayon'])) {
            $query->whereHas('rayon', fn ($q) => $q->where('nom', $donnees['rayon']));
        }

        return response()->json(['data' => $query->orderByDesc('id')->get()]);
    }

    public function jouer(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'chiffre' => ['required', 'integer', 'min:1', 'max:1000000'],
            'prix' => ['required', 'numeric', 'min:0'],
        ]);

        $prixMax = (float) Produit::max('prix');
        $prix = (float) $donnees['prix'];
        $chiffre = (int) $donnees['chiffre'];

        if ($prixMax <= 0 || abs($prix - $prixMax) > 0.001) {
            return response()->json([
                'message' => 'Le montant proposé doit correspondre au prix maximal enregistré.',
                'prix_max' => $prixMax,
            ], 422);
        }

        $somme = 0.0;
        for ($multiple = $chiffre; $multiple < $prixMax; $multiple += $chiffre) {
            $somme += $multiple;
        }

        $gagne = abs($somme - ($prixMax - 1)) < 0.001;
        $message = $gagne
            ? 'Bravo, La livraison vous ai offerte!'
            : 'Désolé ! vous aurez plus de chance la prochaine fois';

        $livebet = Livebet::create([
            'chiffre' => $chiffre,
            'prix_ht' => $prixMax,
            'resultat' => $gagne ? 'gagne' : 'perdu',
            'message' => $message,
        ]);

        return response()->json([
            'data' => $livebet,
            'somme' => $somme,
            'message' => $message,
        ], 201);
    }

    public function livebets(): JsonResponse
    {
        return response()->json(['data' => Livebet::orderByDesc('id')->limit(50)->get()]);
    }
}
