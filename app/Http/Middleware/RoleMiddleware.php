<?php

namespace App\Http\Middleware;

use Closure;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!in_array($request->user()->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden'
            ], 403);
        }
        return $next($request);
    }
}
