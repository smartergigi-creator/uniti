<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanShare
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
   public function handle($request, Closure $next)
{
    if (!auth()->check()) {
        abort(401);
    }

    if (!auth()->user()->hasUnlimitedPdfAccess() && !auth()->user()->can_share) {
        abort(403, 'Share permission denied');
    }

    return $next($request);
}

}
