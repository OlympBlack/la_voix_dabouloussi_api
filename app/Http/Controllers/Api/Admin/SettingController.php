<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => SiteSetting::orderBy('groupe')->orderBy('libelle')->get()]);
    }

    public function update(Request $request, SiteSetting $setting): JsonResponse
    {
        $donnees = $request->validate([
            'valeur' => ['nullable', 'string', 'max:4000'],
            'libelle' => ['nullable', 'string', 'max:180'],
        ]);

        $setting->update($donnees);

        return response()->json(['message' => 'Paramètre mis à jour.', 'data' => $setting]);
    }
}
