<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SerpRememberState
{
    public const COOKIE_NAME = 'serp_auth_state';
    public const COOKIE_MINUTES = 43200; // 30 days

    public function __construct(
        protected CookieJar $cookies,
    ) {
    }

    public function buildLocalState(): array
    {
        return [
            'auth_source' => 'local',
        ];
    }

    public function buildSerpState(array $tokenPayload): array
    {
        return [
            'auth_source' => 'serp',
            'serp_token' => $tokenPayload['token'],
            'serp_refresh' => $tokenPayload['refresh_token'],
            'serp_expiry' => !empty($tokenPayload['expiration'])
                ? Carbon::parse($tokenPayload['expiration'])->toDateTimeString()
                : now()->addMinutes((int) config('jwt.ttl', 60))->toDateTimeString(),
        ];
    }

    public function putSession(array $state): void
    {
        session($state);
    }

    public function syncRememberState(bool $remember, array $state): void
    {
        if ($remember) {
            $this->cookies->queue(
                cookie(
                    self::COOKIE_NAME,
                    json_encode($this->rememberPayload($state), JSON_THROW_ON_ERROR),
                    self::COOKIE_MINUTES,
                    config('session.path', '/'),
                    config('session.domain'),
                    (bool) config('session.secure'),
                    true,
                    false,
                    config('session.same_site', 'lax')
                )
            );

            return;
        }

        $this->forgetRememberState();
    }

    public function forgetRememberState(): void
    {
        $this->cookies->queue(cookie()->forget(
            self::COOKIE_NAME,
            config('session.path', '/'),
            config('session.domain')
        ));
    }

    public function restoreSessionFromRememberCookie(Request $request, ?User $user = null): bool
    {
        $state = $this->readRememberCookie($request);

        if (!$state || empty($state['auth_source'])) {
            return false;
        }

        if (($state['auth_source'] ?? null) === 'local') {
            $this->putSession($this->buildLocalState());
            return true;
        }

        if (($state['auth_source'] ?? null) !== 'serp' || empty($state['serp_refresh'])) {
            return false;
        }

        $response = Http::timeout(15)->withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://api-serp.smarter.com.ph/api/auth/refresh', [
            'refreshToken' => $state['serp_refresh'],
        ]);

        if (!$response->successful()) {
            return false;
        }

        $payload = $this->extractTokenPayload($response->json());

        if (!$payload['token'] || !$payload['refresh_token']) {
            Log::warning('SERP remember cookie refresh response missing token data', [
                'user_id' => $user?->getKey(),
            ]);

            return false;
        }

        $sessionState = $this->buildSerpState($payload);
        $this->putSession($sessionState);

        if ($user) {
            $user->forceFill([
                'serp_token' => $payload['token'],
            ])->save();
        }

        // Keep the remember cookie fresh with the latest refresh token.
        $this->syncRememberState(true, $sessionState);

        return true;
    }

    protected function readRememberCookie(Request $request): ?array
    {
        $value = $request->cookie(self::COOKIE_NAME);

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    protected function rememberPayload(array $state): array
    {
        return array_filter([
            'auth_source' => $state['auth_source'] ?? null,
            'serp_refresh' => $state['serp_refresh'] ?? null,
        ], fn ($value) => filled($value));
    }

    protected function extractTokenPayload(array $payload): array
    {
        $data = $payload['data'] ?? [];

        return [
            'token' => $payload['token'] ?? $payload['accessToken'] ?? $data['token'] ?? $data['accessToken'] ?? null,
            'refresh_token' => $payload['refreshToken'] ?? $payload['refresh_token'] ?? $data['refreshToken'] ?? $data['refresh_token'] ?? null,
            'expiration' => $payload['expiration'] ?? $payload['expiresAt'] ?? $payload['expires_at'] ?? $data['expiration'] ?? $data['expiresAt'] ?? $data['expires_at'] ?? null,
        ];
    }
}
