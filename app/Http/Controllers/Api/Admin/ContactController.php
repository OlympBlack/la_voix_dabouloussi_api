<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $donnees = $request->validate(['statut' => ['nullable', 'in:nouveau,traite,archive']]);
        $query = ContactMessage::query();

        if (! empty($donnees['statut'])) {
            $query->where('statut', $donnees['statut']);
        }

        return response()->json($query->orderByDesc('id')->paginate(20));
    }

    public function update(Request $request, ContactMessage $contact): JsonResponse
    {
        $donnees = $request->validate(['statut' => ['required', 'in:nouveau,traite,archive']]);
        $contact->update($donnees);

        return response()->json(['message' => 'Statut mis à jour.', 'data' => $contact]);
    }

    public function destroy(ContactMessage $contact): JsonResponse
    {
        $contact->delete();

        return response()->json(['message' => 'Message supprimé.']);
    }
}
