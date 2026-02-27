<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;





use Illuminate\Support\Facades\Auth;   // ✅ Correct
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;




class SerpAuthController extends Controller
{
// public function login(Request $request)
// {
//     $request->validate([
//         'username' => 'required',
//         'password' => 'required',
//     ]);

//     try {

//         $res = Http::withHeaders([
//             'Content-Type' => 'application/json',
//         ])->post('https://api-serp.smarter.com.ph/api/auth/login', [
//             'username' => $request->username,
//             'password' => $request->password,
//         ]);

//         if (!$res->successful() || !$res->json('success')) {
//             return back()->with('error','Invalid SERP credentials');
//         }

//         $serp = $res->json();

//         // ✅ Create / Update user
//         $user = User::updateOrCreate(

//             ['serp_id' => $request->username],

//             [
//                 'name'         => $serp['name'] ?? $request->username,
//                 'email'        => $serp['email'] ?? null,
//                 'serp_token'   => $serp['token'],
//                 'status'       => 'active',
//                 'created_from' => 'serp',
//             ]
//         );

//         // ✅ THIS IS THE MAIN FIX 🔥🔥🔥
//         Auth::login($user);

//         // ✅ Session
//         session([
//             'serp_token'   => $serp['token'],
//             'serp_refresh' => $serp['refreshToken'],
//             'serp_expiry'  => Carbon::parse($serp['expiration']),
//         ]);

//         return redirect('/dashboard');

//     } catch (\Throwable $e) {

//         \Log::error('SERP LOGIN ERROR: '.$e->getMessage());

//         return back()->with('error','Login failed');
//     }
// }





public function login(Request $request)
{
    $request->validate([
        'username' => 'required',
        'password' => 'required'
    ]);

    try {
        $res = Http::timeout(15)->withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://api-serp.smarter.com.ph/api/auth/login', [
            'username' => $request->username,
            'password' => $request->password,
        ]);

        if (!$res->successful()) {
            Log::warning('SERP login failed with non-success status', [
                'username' => $request->username,
                'status' => $res->status(),
                'body' => $res->body(),
            ]);

            $status = $res->status();
            $message = match (true) {
                $status === 401, $status === 403 => 'Invalid SERP credentials',
                $status === 429 => 'Too many attempts. Please try again later.',
                $status >= 500 => 'SERP service unavailable. Please try again.',
                default => 'Login failed. Please verify your details.',
            };

            return back()
                ->with('error', $message)
                ->withInput($request->only('username'));
        }

        $serp = $res->json();

        if (!($serp['success'] ?? true)) {
            Log::warning('SERP login returned unsuccessful payload', [
                'username' => $request->username,
                'response' => $serp,
            ]);

            return back()
                ->with('error', 'Invalid SERP credentials')
                ->withInput($request->only('username'));
        }

        $serpId = $request->username;

        // Create / Update user
        $user = User::updateOrCreate(
            ['serp_id' => $serpId],
            [
                'name' => $serp['name'] ?? $serpId,
                'email' => $serp['email'] ?? ($serpId . '@serp.local'),
                'serp_token' => $serp['token'] ?? null,
                'status' => 'active',
                'created_from' => 'serp',
            ]
        );

        if ($user->wasRecentlyCreated) {
            $user->role = 'user';
            $user->can_upload = false;
            $user->can_share = false;
            $user->upload_limit = 0;
            $user->share_limit = 0;
            $user->save();
        } elseif (!$user->role) {
            $user->role = 'user';
            $user->save();
        }



        Auth::guard('web')->login($user);

        // Refresh session
        $request->session()->regenerate();

      // Store SERP tokens for middleware
session([
    'serp_token'   => $serp['token'] ?? null,
    'serp_refresh' => $serp['refreshToken'] ?? null,
    'serp_expiry'  => !empty($serp['expiration'])
        ? Carbon::parse($serp['expiration'])
        : null,
]);


if (!session('serp_token')) {
    Auth::logout();
    $request->session()->invalidate();
    return back()->with('error', 'SERP token missing. Please try again.');
    
}

return redirect('/home');




    } catch (\Throwable $e) {
        Log::error('SERP LOGIN ERROR', [
            'username' => $request->username,
            'message' => $e->getMessage(),
        ]);

        return back()
            ->with('error', 'Login failed. Please try again later.')
            ->withInput($request->only('username'));

    }
}





 

public function logout(Request $request)
{
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
}



public function refreshToken()
{
    $refreshToken = session('serp_refresh');

    if (!$refreshToken) {
        session()->flush();
        return redirect('/login');
    }

    $res = Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post('https://api-serp.smarter.com.ph/api/auth/refresh', [

        'refreshToken' => $refreshToken

    ]);

    if ($res->successful()) {

        session([
            'serp_token'   => $res->json('token'),
            'serp_refresh' => $res->json('refreshToken'),

            // Convert expiry
            'serp_expiry'  => Carbon::parse($res->json('expiration')),
        ]);

        return redirect('/home');
    }

    session()->flush();

    return redirect('/login')
        ->with('error','Session expired. Please login again.');
}


}
