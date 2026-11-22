<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private readonly MediaUploadService $uploadService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'type' => ['nullable', 'in:image,document'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $query = Media::query();

        if (! empty($donnees['type'])) {
            $query->where('type', $donnees['type']);
        }

        if (! empty($donnees['q'])) {
            $query->where('nom', 'like', '%'.$donnees['q'].'%');
        }

        return response()->json($query->orderByDesc('id')->paginate(24));
    }

    public function upload(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'fichier' => ['required', 'file', 'max:20480'],
            'rubrique' => ['nullable', 'string', 'max:40'],
        ]);

        $resultat = $this->uploadService->upload(
            $request->file('fichier'),
            $donnees['rubrique'] ?? 'bibliotheque',
            $donnees['rubrique'] ?? null,
            $request->user()->id
        );

        return response()->json(['message' => 'Fichier téléversé.', 'data' => $resultat], 201);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->upload($request);
    }

    public function destroy(Media $media): JsonResponse
    {
        $this->uploadService->supprimer($media->chemin);
        $media->delete();

        return response()->json(['message' => 'Média supprimé.']);
    }
}
