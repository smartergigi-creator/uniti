<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;


class SerpAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if (session('auth_source') === 'local') {
            return $next($request);
        }

        if (!session()->has('serp_token')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
