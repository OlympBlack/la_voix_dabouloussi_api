<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => User::orderBy('name')->get()->map(fn ($user) => ApiData::user($user)),
        ]);
    }

    public function roles(): JsonResponse
    {
        return response()->json(['data' => Role::with('permissions')->get()]);
    }

    public function permissions(): JsonResponse
    {
        return response()->json(['data' => Permission::orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'exists:roles,name'],
            'actif' => ['nullable', 'boolean'],
        ]);

        $user = User::create($donnees + ['actif' => $donnees['actif'] ?? true]);

        return response()->json(['message' => 'Utilisateur créé.', 'data' => ApiData::user($user)], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'role' => ['required', 'exists:roles,name'],
            'actif' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        if (empty($donnees['password'])) {
            unset($donnees['password']);
        }

        $permissions = $donnees['permissions'] ?? null;
        unset($donnees['permissions']);
        $user->update($donnees);

        if ($permissions !== null) {
            $user->permissions()->sync($permissions);
        }

        return response()->json(['message' => 'Utilisateur mis à jour.', 'data' => ApiData::user($user->fresh())]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }
}
