<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Album;
use Illuminate\Http\JsonResponse;

class AlbumController extends Controller
{
    public function index(): JsonResponse
    {
        $albums = Album::where('statut', 'publie')
            ->withCount('photos')
            ->orderByDesc('date_album')
            ->get();

        return response()->json(['data' => $albums]);
    }

    public function show(Album $album): JsonResponse
    {
        if ($album->statut !== 'publie') {
            abort(404);
        }

        $album->load('photos');

        return response()->json(['data' => $album]);
    }
}
