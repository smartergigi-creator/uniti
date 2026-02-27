<?php




namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use App\Models\User;              
use Illuminate\Support\Facades\Hash; 


class JwtAuthController extends Controller
{
    /* ======================
   JWT REGISTER
===================== */

public function register(Request $request)
{
    $request->validate([
        'name'     => 'required|string|max:100',
        'email'    => 'required|email|unique:users',
        'password' => 'required|min:6',
    ]);

    // Create user
    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
    ]);

    // 🔥 Auto Login after Register
    $token = auth()->login($user);

    return response()->json([
        'status' => true,
        'access_token' => $token,
        'message' => 'Registered & logged in'
    ])->withCookie(
        cookie(
            'jwt_token',
            $token,
            1440,
            '/',
            null,
            false,
            true,
            false,
            'Lax'
        )
    );
}


    /* ======================
       JWT LOGIN
    ====================== */

    public function login(Request $request)
    {
        $credentials = $request->only('email','password');

        if (! $token = auth()->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        return response()->json([
            'access_token' => $token
        ])->withCookie(
            cookie(
                'jwt_token',
                $token,
                1440,
                '/',
                null,
                false,
                true,
                false,
                'Lax'
            )
        );
    }

    /* ======================
       JWT LOGOUT
    ====================== */

    public function logout()
{
    try {

        if (JWTAuth::getToken()) {
            JWTAuth::invalidate(JWTAuth::getToken());
        }

    } catch (\Exception $e) {
        // ignore
    }

    return response()->json([
        'status' => true,
        'message' => 'Logged out'
    ])->withCookie(
        cookie()->forget('jwt_token')
    );
}

}
