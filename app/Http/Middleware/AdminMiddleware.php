<?php

// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;
// use Symfony\Component\HttpFoundation\Response;

// class AdminMiddleware
// {
//     public function handle(Request $request, Closure $next): Response
//     {
//         if (!auth('api')->check() || auth('api')->user()->role !== 'admin') {
//             return response()->json([
//                 'message' => 'Access denied. Admin only.'
//             ], 403);
//         }

//         return $next($request);
//     }
// }




namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;


class AdminMiddleware
{
    // public function handle(Request $request, Closure $next): Response
    // {
       
    //     if (!auth('api')->check()) {
    //         return response()->json([
    //             'message' => 'Unauthorized'
    //         ], 401);
    //     }

    //     if (auth('api')->user()->role !== 'admin') {
    //         return response()->json([
    //             'message' => 'Access denied. Admin only.'
    //         ], 403);
    //     }

    //     return $next($request);
    // }

    public function handle(Request $request, Closure $next)
    {
        // Web authentication check
        if (!Auth::check()) {
            return redirect('/login');
        }

        // Role check
        if (Auth::user()->role !== 'admin') {
            return abort(403, 'Access Denied');
        }

        return $next($request);
    }
}
