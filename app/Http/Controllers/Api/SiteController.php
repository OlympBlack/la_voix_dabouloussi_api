<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Contenu;
use App\Models\SiteSetting;
use App\Support\ApiData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function settings(): JsonResponse
    {
        $settings = SiteSetting::orderBy('groupe')->orderBy('libelle')->get()
            ->groupBy('groupe')
            ->map(fn ($groupe) => $groupe->mapWithKeys(fn ($item) => [$item->cle => $item->valeur]));

        return response()->json(['data' => $settings]);
    }

    public function home(): JsonResponse
    {
        $publies = Contenu::where('statut', 'publie')->orderByDesc('date_publication');

        $featured = (clone $publies)->where('a_la_une', true)->limit(8)->get();
        $journaux = (clone $publies)->where('rubrique', 'journal')->limit(6)->get();
        $actualites = (clone $publies)->where('rubrique', 'actualite')->limit(6)->get();
        $jda = Contenu::where('statut', 'publie')->where('rubrique', 'jda')
            ->orderBy('meta->ordre')
            ->orderByDesc('date_publication')
            ->limit(4)
            ->get();
        $parlement = (clone $publies)->where('rubrique', 'parlement')->limit(4)->get();
        $contes = (clone $publies)->where('rubrique', 'conte')->limit(3)->get();
        $poemes = (clone $publies)->where('rubrique', 'poeme')->limit(3)->get();
        $plans = (clone $publies)->where('rubrique', 'plan')->orderBy('id')->get();
        $galerie = (clone $publies)->where('rubrique', 'image')->orderBy('id')->get();

        $collection = fn ($items) => $items->map(fn ($item) => ApiData::contenu($item))->values()->all();

        return response()->json([
            'data' => [
                'featured' => $collection($featured),
                'journaux' => $collection($journaux),
                'actualites' => $collection($actualites),
                'jda' => $collection($jda),
                'parlement' => $collection($parlement),
                'contes' => $collection($contes),
                'poemes' => $collection($poemes),
                'objectif' => $collection((clone $publies)->where('rubrique', 'objectif')->limit(1)->get()),
                'identification' => $collection((clone $publies)->where('rubrique', 'identification')->limit(1)->get()),
                'plans' => $collection($plans),
                'galerie' => $collection($galerie),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'rubrique' => ['nullable', 'string', 'max:40'],
            'annee' => ['nullable', 'digits:4'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Contenu::where('statut', 'publie');

        if (! empty($donnees['q'])) {
            $terme = '%'.$donnees['q'].'%';
            $query->where(function ($q) use ($terme): void {
                $q->where('titre', 'like', $terme)
                    ->orWhere('resume', 'like', $terme)
                    ->orWhere('corps', 'like', $terme)
                    ->orWhere('auteur', 'like', $terme);
            });
        }

        if (! empty($donnees['rubrique'])) {
            $query->where('rubrique', $donnees['rubrique']);
        }

        if (! empty($donnees['annee'])) {
            $query->whereYear('date_publication', $donnees['annee']);
        }

        $resultats = $query->orderByDesc('date_publication')
            ->paginate($donnees['per_page'] ?? 12)
            ->through(fn ($item) => ApiData::contenu($item));

        return response()->json($resultats);
    }

    public function contact(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:180'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'objet' => ['nullable', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $donnees['ip'] = $request->ip();
        ContactMessage::create($donnees);

        return response()->json(['message' => 'Votre message a été enregistré. Merci de nous avoir contactés.'], 201);
    }
}
