<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SerpAuthController extends Controller
{
    protected function extractTokenPayload(array $payload): array
    {
        $data = $payload['data'] ?? [];
        $user = $payload['user'] ?? $data['user'] ?? [];

        return [
            'token' => $payload['token'] ?? $payload['accessToken'] ?? $data['token'] ?? $data['accessToken'] ?? null,
            'refresh_token' => $payload['refreshToken'] ?? $payload['refresh_token'] ?? $data['refreshToken'] ?? $data['refresh_token'] ?? null,
            'expiration' => $payload['expiration'] ?? $payload['expiresAt'] ?? $payload['expires_at'] ?? $data['expiration'] ?? $data['expiresAt'] ?? $data['expires_at'] ?? null,
            'name' => $payload['name'] ?? $data['name'] ?? ($user['name'] ?? null),
            'email' => $payload['email'] ?? $data['email'] ?? ($user['email'] ?? null),
        ];
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $serpId = trim((string) $request->username);

        try {
            $response = Http::timeout(15)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://api-serp.smarter.com.ph/api/auth/login', [
                'username' => $request->username,
                'password' => $request->password,
            ]);

            // 🔥 STEP 1: If SERP fails → fallback to DB login
            if (!$response->successful()) {

                $user = User::where('serp_id', $serpId)
                    ->orWhere('name', $serpId)
                    ->first();

                if ($user) {
                    Auth::login($user);
                    $request->session()->regenerate();

                    return redirect('/home');
                }

                return back()
                    ->with('error', 'Invalid credentials')
                    ->withInput($request->only('username'));
            }

            $serp = $response->json();

            // 🔥 STEP 2: If SERP response invalid → fallback
            if (!($serp['success'] ?? true)) {

                $user = User::where('serp_id', $serpId)
                    ->orWhere('name', $serpId)
                    ->first();

                if ($user) {
                    Auth::login($user);
                    $request->session()->regenerate();

                    return redirect('/home');
                }

                return back()
                    ->with('error', 'Invalid SERP credentials')
                    ->withInput($request->only('username'));
            }

            // 🔥 STEP 3: Extract token
            $tokenPayload = $this->extractTokenPayload($serp);

            if (!$tokenPayload['token'] || !$tokenPayload['refresh_token']) {
                return back()
                    ->with('error', 'SERP login response missing token details.')
                    ->withInput($request->only('username'));
            }

            $legacyName = isset($tokenPayload['name']) ? trim((string) $tokenPayload['name']) : null;

            $identifiers = collect([$serpId, $legacyName])
                ->filter(fn ($value) => filled($value))
                ->unique()
                ->values();

            // 🔥 STEP 4: Find user
            $user = User::where(function ($query) use ($identifiers) {
                $query->whereIn('serp_id', $identifiers)
                    ->orWhereIn('name', $identifiers);
            })->first();

            if ($user) {
                $user->update([
                    'serp_id' => $user->serp_id ?? $serpId,
                    'name' => $tokenPayload['name'] ?? $user->name,
                    'email' => $tokenPayload['email'] ?? $user->email,
                    'serp_token' => $tokenPayload['token'],
                    'status' => 'active',
                ]);
            } else {
                $user = User::create([
                    'serp_id' => $serpId,
                    'name' => $tokenPayload['name'] ?? $serpId,
                    'email' => $tokenPayload['email'] ?? ($serpId . '@serp.local'),
                    'serp_token' => $tokenPayload['token'],
                    'status' => 'active',
                    'created_from' => 'serp',
                    'role' => 'user',
                    'can_upload' => false,
                    'can_share' => false,
                    'upload_limit' => 0,
                    'share_limit' => 0,
                ]);
            }

            if (!$user->role) {
                $user->role = 'user';
                $user->save();
            }

            // 🔥 STEP 5: Login
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            session([
                'auth_source' => 'serp',
                'serp_token' => $tokenPayload['token'],
                'serp_refresh' => $tokenPayload['refresh_token'],
                'serp_expiry' => !empty($tokenPayload['expiration'])
                    ? Carbon::parse($tokenPayload['expiration'])->toDateTimeString()
                    : now()->addMinutes((int) config('jwt.ttl', 60))->toDateTimeString(),
            ]);

            return redirect('/home');

        } catch (\Throwable $e) {

            Log::error('SERP LOGIN ERROR', [
                'username' => $request->username,
                'message' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Login failed. Please try again.')
                ->withInput($request->only('username'));
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->forget(['auth_source', 'serp_token', 'serp_refresh', 'serp_expiry']);
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

        $response = Http::timeout(15)->withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://api-serp.smarter.com.ph/api/auth/refresh', [
            'refreshToken' => $refreshToken,
        ]);

        if ($response->successful()) {
            $payload = $this->extractTokenPayload($response->json());

            if ($payload['token'] && $payload['refresh_token']) {
                session([
                    'auth_source' => 'serp',
                    'serp_token' => $payload['token'],
                    'serp_refresh' => $payload['refresh_token'],
                    'serp_expiry' => !empty($payload['expiration'])
                        ? Carbon::parse($payload['expiration'])->toDateTimeString()
                        : now()->addMinutes((int) config('jwt.ttl', 60))->toDateTimeString(),
                ]);

                if ($user = Auth::user()) {
                    $user->forceFill(['serp_token' => $payload['token']])->save();
                }

                return redirect('/home');
            }
        }

        session()->flush();

        return redirect('/login')
            ->with('error', 'Session expired. Please login again.');
    }
}
