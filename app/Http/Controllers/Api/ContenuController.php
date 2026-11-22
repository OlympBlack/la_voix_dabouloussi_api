<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contenu;
use App\Support\ApiData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContenuController extends Controller
{
    public function index(Request $request, string $rubrique): JsonResponse
    {
        $donnees = $request->validate([
            'categorie' => ['nullable', 'string', 'max:170'],
            'annee' => ['nullable', 'digits:4'],
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Contenu::where('statut', 'publie')->where('rubrique', $rubrique);

        if (! empty($donnees['categorie'])) {
            $query->whereHas('categorie', fn ($q) => $q->where('slug', $donnees['categorie']));
        }

        if (! empty($donnees['annee'])) {
            $query->whereYear('date_publication', $donnees['annee']);
        }

        if (! empty($donnees['q'])) {
            $terme = '%'.$donnees['q'].'%';
            $query->where(function ($q) use ($terme): void {
                $q->where('titre', 'like', $terme)->orWhere('resume', 'like', $terme);
            });
        }

        $resultats = $query->with('categorie')
            ->orderByDesc('date_publication')
            ->orderByDesc('id')
            ->paginate($donnees['per_page'] ?? 12)
            ->through(fn ($item) => ApiData::contenu($item));

        return response()->json($resultats);
    }

    public function journaux(Request $request): JsonResponse
    {
        return $this->index($request, 'journal');
    }

    public function jda(Request $request): JsonResponse
    {
        return $this->index($request, 'jda');
    }

    public function parlement(Request $request): JsonResponse
    {
        return $this->index($request, 'parlement');
    }

    public function commissions(Request $request): JsonResponse
    {
        return $this->index($request, 'commission');
    }

    public function contes(Request $request): JsonResponse
    {
        return $this->index($request, 'conte');
    }

    public function poemes(Request $request): JsonResponse
    {
        return $this->index($request, 'poeme');
    }

    public function actualites(Request $request): JsonResponse
    {
        return $this->index($request, 'actualite');
    }

    public function show(string $contenu): JsonResponse
    {
        $enregistrement = Contenu::where('slug', $contenu)->firstOrFail();

        if ($enregistrement->statut !== 'publie') {
            abort(404);
        }

        $enregistrement->increment('vues');
        $enregistrement->load('categorie');

        $similaires = Contenu::where('statut', 'publie')
            ->where('rubrique', $enregistrement->rubrique)
            ->where('id', '!=', $enregistrement->id)
            ->orderByDesc('date_publication')
            ->limit(3)
            ->get()
            ->map(fn ($item) => ApiData::contenu($item));

        return response()->json([
            'data' => ApiData::contenu($enregistrement),
            'similaires' => $similaires,
        ]);
    }
}
