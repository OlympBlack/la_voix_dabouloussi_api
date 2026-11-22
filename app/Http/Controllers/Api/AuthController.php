<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $donnees['email'])->first();

        if (! $user || ! Hash::check($donnees['password'], $user->password) || ! $user->actif) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants invalides ou compte désactivé.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'message' => 'Connexion réussie.',
            'access_token' => $user->createToken('interface-'.now()->format('YmdHis')),
            'token_type' => 'Bearer',
            'user' => ApiData::user($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => ApiData::user($request->user())]);
    }

    public function update(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email,'.$request->user()->id],
        ]);

        $request->user()->update($donnees);

        return response()->json(['message' => 'Profil mis à jour.', 'user' => ApiData::user($request->user())]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($donnees['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => ['Mot de passe actuel incorrect.']]);
        }

        $request->user()->update(['password' => $donnees['password']]);

        return response()->json(['message' => 'Mot de passe modifié.']);
    }
}
