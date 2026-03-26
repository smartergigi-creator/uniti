<?php

namespace App\Http\Middleware;

use App\Support\SerpRememberState;
use Closure;
use Illuminate\Http\Request;

class SerpAuth
{
    public function __construct(
        protected SerpRememberState $rememberState,
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if (!session()->has('auth_source')) {
            $restored = $this->rememberState->restoreSessionFromRememberCookie($request, auth()->user());

            if (!$restored) {
                $this->rememberState->forgetRememberState();
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login');
            }
        }

        $authSource = session('auth_source');

        if ($authSource === 'local') {
            return $next($request);
        }

        if ($authSource !== 'serp' || !session()->has('serp_token') || !session()->has('serp_refresh')) {
            $this->rememberState->forgetRememberState();
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login');
        }

        return $next($request);
    }
}
