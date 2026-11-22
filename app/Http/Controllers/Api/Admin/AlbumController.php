<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\AlbumPhoto;
use App\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AlbumController extends Controller
{
    public function __construct(private readonly MediaUploadService $uploadService)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => Album::withCount('photos')->orderByDesc('id')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'titre' => ['required', 'string', 'max:220'],
            'description' => ['nullable', 'string'],
            'rubrique' => ['required', 'string', 'max:40'],
            'date_album' => ['nullable', 'date'],
            'statut' => ['required', 'in:brouillon,publie,archive'],
            'couverture' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,jfif', 'max:8192'],
        ]);

        if ($request->hasFile('couverture')) {
            $donnees['couverture'] = $this->uploadService->upload(
                $request->file('couverture'), 'couverture', $donnees['rubrique'], $request->user()->id
            )['chemin'];
        }

        $donnees['slug'] = $this->slug($donnees['titre']);
        $album = Album::create($donnees);

        return response()->json(['message' => 'Album créé.', 'data' => $album], 201);
    }

    public function update(Request $request, Album $album): JsonResponse
    {
        $donnees = $request->validate([
            'titre' => ['required', 'string', 'max:220'],
            'description' => ['nullable', 'string'],
            'rubrique' => ['required', 'string', 'max:40'],
            'date_album' => ['nullable', 'date'],
            'statut' => ['required', 'in:brouillon,publie,archive'],
            'couverture' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,jfif', 'max:8192'],
        ]);

        if ($request->hasFile('couverture')) {
            $this->uploadService->supprimer($album->couverture);
            $donnees['couverture'] = $this->uploadService->upload(
                $request->file('couverture'), 'couverture', $donnees['rubrique'], $request->user()->id
            )['chemin'];
        }

        $album->update($donnees);

        return response()->json(['message' => 'Album mis à jour.', 'data' => $album]);
    }

    public function destroy(Album $album): JsonResponse
    {
        $this->uploadService->supprimer($album->couverture);
        foreach ($album->photos as $photo) {
            $this->uploadService->supprimer($photo->image);
        }
        $album->delete();

        return response()->json(['message' => 'Album supprimé.']);
    }

    public function addPhoto(Request $request, Album $album): JsonResponse
    {
        $donnees = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,jfif', 'max:8192'],
            'legende' => ['nullable', 'string', 'max:220'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ]);

        $fichier = $this->uploadService->upload(
            $request->file('image'), 'photo', $album->rubrique, $request->user()->id
        );

        $photo = $album->photos()->create([
            'image' => $fichier['chemin'],
            'legende' => $donnees['legende'] ?? null,
            'ordre' => $donnees['ordre'] ?? $album->photos()->count(),
        ]);

        return response()->json(['message' => 'Photo ajoutée.', 'data' => $photo], 201);
    }

    public function deletePhoto(Album $album, AlbumPhoto $photo): JsonResponse
    {
        if ($photo->album_id !== $album->id) {
            abort(404);
        }

        $this->uploadService->supprimer($photo->image);
        $photo->delete();

        return response()->json(['message' => 'Photo supprimée.']);
    }

    private function slug(string $titre): string
    {
        $base = Str::slug($titre) ?: 'album';
        $slug = $base;
        $suffixe = 2;

        while (Album::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffixe++;
        }

        return $slug;
    }
}
