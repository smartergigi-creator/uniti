<?php

namespace App\Http\Middleware;

use App\Support\SerpRememberState;
use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;


class SerpRefreshMiddleware
{
    public function __construct(
        protected SerpRememberState $rememberState,
    ) {
    }

    public function handle($request, Closure $next)
    {
        if (session('auth_source') === 'local') {
            return $next($request);
        }

        $expiry  = session('serp_expiry');
        $refresh = session('serp_refresh');

        if (!$expiry || !$refresh) {
            session()->flush();
            $this->rememberState->forgetRememberState();

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
                $this->rememberState->forgetRememberState();

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
                $this->rememberState->forgetRememberState();

                return redirect('/login');
            }

            $this->rememberState->putSession([
                'auth_source' => 'serp',
                'serp_token' => $token,
                'serp_refresh' => $refreshToken,
                'serp_expiry' => $expiration
                    ? Carbon::parse($expiration)->toDateTimeString()
                    : now()->addMinutes((int) config('jwt.ttl', 60))->toDateTimeString(),
            ]);

            if ($request->hasCookie(SerpRememberState::COOKIE_NAME)) {
                $this->rememberState->syncRememberState(true, session()->only([
                    'auth_source',
                    'serp_token',
                    'serp_refresh',
                    'serp_expiry',
                ]));
            }
        }

        return $next($request);
    }
}
