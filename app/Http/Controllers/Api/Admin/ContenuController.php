<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contenu;
use App\Services\MediaUploadService;
use App\Support\ApiData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContenuController extends Controller
{
    public function __construct(private readonly MediaUploadService $uploadService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'rubrique' => ['nullable', 'string', 'max:40'],
            'statut' => ['nullable', 'in:brouillon,publie,archive'],
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Contenu::with('categorie');

        if (! empty($donnees['rubrique'])) {
            $query->where('rubrique', $donnees['rubrique']);
        }

        if (! empty($donnees['statut'])) {
            $query->where('statut', $donnees['statut']);
        }

        if (! empty($donnees['q'])) {
            $terme = '%'.$donnees['q'].'%';
            $query->where('titre', 'like', $terme);
        }

        $resultats = $query->orderByDesc('date_publication')->orderByDesc('id')
            ->paginate($donnees['per_page'] ?? 20)
            ->through(fn ($item) => ApiData::contenu($item));

        return response()->json($resultats);
    }

    public function store(Request $request): JsonResponse
    {
        $donnees = $this->valider($request);
        $donnees = $this->traiterFichiers($request, $donnees);
        $donnees['slug'] = ApiData::slug($donnees['titre'], $donnees['rubrique']);
        $donnees['user_id'] = $request->user()->id;
        $donnees['meta'] = $this->meta($request);

        $contenu = Contenu::create($donnees);

        return response()->json([
            'message' => 'Contenu créé.',
            'data' => ApiData::contenu($contenu),
        ], 201);
    }

    public function show(Contenu $contenu): JsonResponse
    {
        return response()->json(['data' => ApiData::contenu($contenu)]);
    }

    public function update(Request $request, Contenu $contenu): JsonResponse
    {
        $donnees = $this->valider($request, $contenu);
        $donnees = $this->traiterFichiers($request, $donnees, $contenu);
        $donnees['meta'] = $this->meta($request, $contenu);

        $contenu->update($donnees);

        return response()->json([
            'message' => 'Contenu mis à jour.',
            'data' => ApiData::contenu($contenu->fresh()),
        ]);
    }

    public function destroy(Contenu $contenu): JsonResponse
    {
        $this->uploadService->supprimer($contenu->image);
        $this->uploadService->supprimer($contenu->document);
        $contenu->delete();

        return response()->json(['message' => 'Contenu supprimé.']);
    }

    public function statut(Request $request, Contenu $contenu): JsonResponse
    {
        $donnees = $request->validate(['statut' => ['required', 'in:brouillon,publie,archive']]);
        $contenu->update(['statut' => $donnees['statut']]);

        return response()->json(['message' => 'Statut mis à jour.', 'data' => ApiData::contenu($contenu)]);
    }

    private function valider(Request $request, ?Contenu $contenu = null): array
    {
        $regles = [
            'rubrique' => ['required', 'string', 'max:40'],
            'titre' => ['required', 'string', 'max:220'],
            'resume' => ['nullable', 'string', 'max:600'],
            'corps' => ['nullable', 'string'],
            'auteur' => ['nullable', 'string', 'max:150'],
            'date_publication' => ['nullable', 'date'],
            'statut' => ['required', 'in:brouillon,publie,archive'],
            'categorie_id' => ['nullable', 'exists:categories,id'],
            'document_nom' => ['nullable', 'string', 'max:220'],
            'a_la_une' => ['nullable', 'boolean'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,jfif', 'max:8192'],
            'document' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip', 'max:20480'],
        ];

        return $request->validate($regles);
    }

    private function traiterFichiers(Request $request, array $donnees, ?Contenu $contenu = null): array
    {
        unset($donnees['image'], $donnees['document']);

        if ($request->hasFile('image')) {
            if ($contenu?->image) {
                $this->uploadService->supprimer($contenu->image);
            }
            $donnees['image'] = $this->uploadService->upload(
                $request->file('image'), 'couverture', $donnees['rubrique'], $request->user()->id
            )['chemin'];
        }

        if ($request->hasFile('document')) {
            if ($contenu?->document) {
                $this->uploadService->supprimer($contenu->document);
            }
            $fichier = $this->uploadService->upload(
                $request->file('document'), 'document', $donnees['rubrique'], $request->user()->id
            );
            $donnees['document'] = $fichier['chemin'];
            $donnees['document_nom'] = $donnees['document_nom'] ?: $fichier['nom'];
        }

        if (! array_key_exists('a_la_une', $donnees)) {
            $donnees['a_la_une'] = false;
        }

        return $donnees;
    }

    private function meta(Request $request, ?Contenu $contenu = null): array
    {
        $donnees = $request->validate(['meta' => ['nullable', 'array']]);

        return array_merge($contenu?->meta ?? [], $donnees['meta'] ?? []);
    }
}
