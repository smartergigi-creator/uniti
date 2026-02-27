<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class WebAuthMiddleware
{
public function handle($request, Closure $next)
{
    try {

        $token = $request->cookie('jwt_token');

        if (!$token) {
            return redirect('/login');
        }

        JWTAuth::setToken($token)->authenticate();

        return $next($request);

    } catch (\Exception $e) {

        return redirect('/login')
            ->withCookie(cookie()->forget('jwt_token'));
    }
}



}
