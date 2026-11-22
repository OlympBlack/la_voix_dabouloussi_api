<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleApiCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $origins = config('app.cors_origins', '*');
        $origin = $request->headers->get('Origin');
        $allowed = $origins === '*' ? ($origin ?: '*') : (in_array($origin, $origins, true) ? $origin : $origins[0]);

        if ($request->getMethod() === 'OPTIONS') {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $allowed)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        return $response
            ->header('Access-Control-Allow-Origin', $allowed)
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN')
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Vary', 'Origin');
    }
}
