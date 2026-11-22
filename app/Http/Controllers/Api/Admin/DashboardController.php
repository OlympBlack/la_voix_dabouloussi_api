<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Categorie;
use App\Models\ContactMessage;
use App\Models\Contenu;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $parRubrique = Contenu::selectRaw('rubrique, COUNT(*) as total')
            ->groupBy('rubrique')
            ->pluck('total', 'rubrique')
            ->mapWithKeys(fn ($total, $rubrique) => [$rubrique => (int) $total]);

        $total = (int) Contenu::count();

        return response()->json([
            'data' => [
                'journaux' => $parRubrique->get('journal', 0),
                'articles' => $parRubrique->get('jda', 0) + $parRubrique->get('actualite', 0),
                'parlement' => $parRubrique->get('parlement', 0) + $parRubrique->get('commission', 0),
                'contes' => $parRubrique->get('conte', 0),
                'poemes' => $parRubrique->get('poeme', 0),
                'total_contenus' => $total,
                'publies' => (int) Contenu::where('statut', 'publie')->count(),
                'brouillons' => (int) Contenu::where('statut', 'brouillon')->count(),
                'archives' => (int) Contenu::where('statut', 'archive')->count(),
                'albums' => (int) Album::count(),
                'medias' => (int) Media::count(),
                'categories' => (int) Categorie::count(),
                'utilisateurs' => (int) User::count(),
                'messages' => (int) ContactMessage::where('statut', 'nouveau')->count(),
                'par_rubrique' => $parRubrique,
                'recent' => Contenu::orderByDesc('id')->limit(6)
                    ->get(['id', 'titre', 'rubrique', 'statut', 'date_publication']),
            ],
        ]);
    }
}
