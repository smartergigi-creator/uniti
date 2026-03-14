<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;


class SerpRefreshMiddleware
{
    public function handle($request, Closure $next)
    {
        $expiry  = session('serp_expiry');
        $refresh = session('serp_refresh');

        if (!$expiry || !$refresh) {
            session()->flush();
            return redirect('/login');
        }

        $expiryTime = Carbon::parse($expiry);

        if (now()->addMinutes(2)->greaterThan($expiryTime)) {
            $res = Http::timeout(15)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://api-serp.smarter.com.ph/api/auth/refresh', [
                'refreshToken' => $refresh
            ]);

            if (!$res->successful()) {
                session()->flush();
                return redirect('/login');
            }

            $payload = $res->json();
            $token = $payload['token'] ?? $payload['accessToken'] ?? $payload['data']['token'] ?? $payload['data']['accessToken'] ?? null;
            $refreshToken = $payload['refreshToken'] ?? $payload['refresh_token'] ?? $payload['data']['refreshToken'] ?? $payload['data']['refresh_token'] ?? null;
            $expiration = $payload['expiration'] ?? $payload['expiresAt'] ?? $payload['expires_at'] ?? $payload['data']['expiration'] ?? $payload['data']['expiresAt'] ?? $payload['data']['expires_at'] ?? null;

            if (!$token || !$refreshToken) {
                Log::warning('SERP refresh response missing token data', [
                    'response' => $payload,
                ]);

                session()->flush();
                return redirect('/login');
            }

            session([
                'auth_source' => 'serp',
                'serp_token' => $token,
                'serp_refresh' => $refreshToken,
                'serp_expiry' => $expiration
                    ? Carbon::parse($expiration)->toDateTimeString()
                    : now()->addMinutes((int) config('jwt.ttl', 60))->toDateTimeString(),
            ]);
        }

        return $next($request);
    }
}
