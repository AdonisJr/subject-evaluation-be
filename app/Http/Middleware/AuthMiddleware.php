<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if (!auth('sanctum')->check()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        return $next($request);
    }
}
