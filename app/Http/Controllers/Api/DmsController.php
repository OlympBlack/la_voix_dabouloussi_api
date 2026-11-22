<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contenu;
use App\Models\DmsLike;
use App\Support\ApiData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DmsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $donnees = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $query = Contenu::where('statut', 'publie')->where('rubrique', 'dms');

        if (! empty($donnees['q'])) {
            $terme = '%'.$donnees['q'].'%';
            $query->where('titre', 'like', $terme);
        }

        $articles = $query->orderByDesc('date_publication')
            ->paginate(9)
            ->through(fn ($item) => ApiData::contenu($item));

        return response()->json($articles);
    }

    public function show(string $contenu): JsonResponse
    {
        $enregistrement = Contenu::where('slug', $contenu)->firstOrFail();

        if ($enregistrement->statut !== 'publie' || $enregistrement->rubrique !== 'dms') {
            abort(404);
        }

        return response()->json(['data' => ApiData::contenu($enregistrement)]);
    }

    public function like(Request $request, Contenu $contenu): JsonResponse
    {
        if ($contenu->statut !== 'publie' || $contenu->rubrique !== 'dms') {
            abort(404);
        }

        $visiteur = hash('sha256', ($request->ip() ?? 'inconnu').'|'.$request->userAgent());
        $existant = DmsLike::where('contenu_id', $contenu->id)->where('visiteur', $visiteur)->first();

        if ($existant) {
            $existant->delete();
            $message = 'J\'ai retiré mon appréciation.';
        } else {
            DmsLike::create(['contenu_id' => $contenu->id, 'visiteur' => $visiteur]);
            $message = 'Merci pour votre appréciation !';
        }

        return response()->json([
            'message' => $message,
            'likes' => $contenu->dmsLikes()->count(),
        ]);
    }
}
