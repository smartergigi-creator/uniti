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

        try {
            $response = Http::timeout(15)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://api-serp.smarter.com.ph/api/auth/login', [
                'username' => $request->username,
                'password' => $request->password,
            ]);

            if (!$response->successful()) {
                Log::warning('SERP login failed with non-success status', [
                    'username' => $request->username,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $status = $response->status();

                if (in_array($status, [401, 403, 422], true)) {
                    return back()
                        ->with('error', 'Invalid SERP credentials')
                        ->withInput($request->only('username'));
                }

                if ($status === 429) {
                    return back()
                        ->with('error', 'Too many attempts. Please try again later.')
                        ->withInput($request->only('username'));
                }

                return back()
                    ->with('error', 'SERP login unavailable. Please try again later.')
                    ->withInput($request->only('username'));
            }

            $serp = $response->json();

            if (!($serp['success'] ?? true)) {
                Log::warning('SERP login returned unsuccessful payload', [
                    'username' => $request->username,
                    'response' => $serp,
                ]);

                return back()
                    ->with('error', 'Invalid SERP credentials')
                    ->withInput($request->only('username'));
            }

            $tokenPayload = $this->extractTokenPayload($serp);

            if (!$tokenPayload['token'] || !$tokenPayload['refresh_token']) {
                Log::warning('SERP login response missing token data', [
                    'username' => $request->username,
                    'response' => $serp,
                ]);

                return back()
                    ->with('error', 'SERP login response missing token details.')
                    ->withInput($request->only('username'));
            }

            $serpId = trim((string) $request->username);

            $user = User::updateOrCreate(
                ['serp_id' => $serpId],
                [
                    'name' => $tokenPayload['name'] ?? $serpId,
                    'email' => $tokenPayload['email'] ?? ($serpId . '@serp.local'),
                    'serp_token' => $tokenPayload['token'],
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
                ->with('error', 'SERP login failed. Please try again later.')
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
