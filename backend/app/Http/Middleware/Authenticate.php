<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    // Belum ada halaman login web. Nanti diganti ke route login admin saat P5.
    // Untuk API, null berarti jawab 401 (bukan redirect).
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}