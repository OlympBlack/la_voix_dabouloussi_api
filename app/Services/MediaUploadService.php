<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaUploadService
{
    public const IMAGE_MAX_KO = 8192;
    public const DOCUMENT_MAX_KO = 20480;

    public function upload(UploadedFile $fichier, string $categorie = 'images', ?string $rubrique = null, ?int $userId = null): array
    {
        $extension = strtolower($fichier->getClientOriginalExtension());
        $estImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'], true);
        $estDocument = in_array($extension, ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip'], true);

        if (! $estImage && ! $estDocument) {
            throw ValidationException::withMessages(['fichier' => ['Format non autorisé (images, PDF et documents Office uniquement).']]);
        }

        $max = $estImage ? self::IMAGE_MAX_KO : self::DOCUMENT_MAX_KO;

        if ($fichier->getSize() > $max * 1024) {
            throw ValidationException::withMessages(['fichier' => ["Fichier trop volumineux (maximum {$max} Ko)."]]);
        }

        $dossier = $estImage ? 'images' : 'documents';
        $chemin = $fichier->store($dossier.'/'.now()->format('Y/m'), 'public');

        $media = Media::create([
            'user_id' => $userId,
            'nom' => $fichier->getClientOriginalName(),
            'chemin' => $chemin,
            'type' => $estImage ? 'image' : 'document',
            'mime' => $fichier->getMimeType(),
            'taille' => $fichier->getSize(),
            'alt' => $categorie,
            'rubrique' => $rubrique,
        ]);

        return [
            'id' => $media->id,
            'chemin' => $chemin,
            'url' => $media->url,
            'type' => $media->type,
            'nom' => $media->nom,
        ];
    }

    public function supprimer(?string $chemin): void
    {
        if ($chemin && Storage::disk('public')->exists($chemin)) {
            Storage::disk('public')->delete($chemin);
        }
    }
}
