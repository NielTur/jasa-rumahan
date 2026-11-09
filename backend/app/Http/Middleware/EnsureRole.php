<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

// Pemakaian di route: ->middleware('role:customer') atau 'role:mitra'
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            return ApiResponse::error('Anda tidak punya akses ke data ini.', 403);
        }

        return $next($request);
    }
}