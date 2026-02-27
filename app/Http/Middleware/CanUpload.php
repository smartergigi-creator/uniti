<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanUpload
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

    $user = auth()->user();
    $hasUploadAccess = $user->role === 'admin'
        || $user->can_upload;

    if (!$hasUploadAccess) {
        abort(403, 'Upload permission denied');
    }

    return $next($request);
}

}
