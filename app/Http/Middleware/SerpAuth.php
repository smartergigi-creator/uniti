<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;


class SerpAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Debug (temporary)
        //  dd('Middleware', session()->all());

        if (!session()->has('serp_token')) { 
            return redirect('/login');
        }

        

        return $next($request);
    }
}
