<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        $plain = str_starts_with($header, 'Bearer ') ? trim(substr($header, 7)) : null;

        if (! $plain) {
            return response()->json(['message' => 'Authentification requise.'], 401);
        }

        $token = PersonalAccessToken::where('token', hash('sha256', $plain))->first();

        if (! $token || ($token->expires_at && $token->expires_at->isPast())) {
            return response()->json(['message' => 'Jeton invalide ou expiré.'], 401);
        }

        $user = $token->user;

        if (! $user || ! $user->actif) {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
