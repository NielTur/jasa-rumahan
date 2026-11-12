<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Pagar halaman /admin (guard web, session). Beda dengan EnsureRole yang membalas JSON untuk API.
// - Belum login        -> diarahkan ke halaman login admin
// - Bukan admin aktif  -> 403
class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->guest(route('admin.login'));
        }

        if ($user->role !== 'admin' || $user->account_status !== 'active') {
            abort(403, 'Anda tidak punya akses ke halaman admin.');
        }

        return $next($request);
    }
}