<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /* ======================
       WEB LOGIN
    ====================== */

    public function loginWeb(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {

            $request->session()->regenerate();

            return redirect('/home');
        }

        return back()->with('error', 'Invalid email or password');
    }
    public function login(Request $request)
    {
        $credentials = $request->only('email','password');

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['message'=>'Invalid'],401);
        }

        return response()->json([
            'access_token' => $token
        ])->withCookie(
            cookie(
                'jwt_token',   // name
                $token,        // value
                1440,          // minutes
                '/',           // path
                null,          // domain (AUTO)
                false,         // secure (localhost)
                true,          // httpOnly
                false,
                'Lax'          // IMPORTANT
            )
        );
    }



    /* ======================
       WEB LOGOUT
    ====================== */

    public function logoutWeb(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
