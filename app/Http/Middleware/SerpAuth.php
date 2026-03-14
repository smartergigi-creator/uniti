<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SerpAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if (session('auth_source') !== 'serp' || !session()->has('serp_token') || !session()->has('serp_refresh')) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login');
        }

        return $next($request);
    }
}
