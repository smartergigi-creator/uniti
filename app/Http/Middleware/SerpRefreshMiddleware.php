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

        // Refresh before 2 mins
        if (now()->addMinutes(2)->greaterThan($expiryTime)) {
        // if (true) {



// Log::info('SERP TOKEN REFRESH TRIGGERED');
            $res = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://api-serp.smarter.com.ph/api/auth/refresh', [
                'refreshToken' => $refresh
            ]);

            if (!$res->successful()) {
                session()->flush();
                return redirect('/login');
            }

            session([
                'serp_token'   => $res->json('token'),
                'serp_refresh' => $res->json('refreshToken'),
                'serp_expiry'  => Carbon::parse($res->json('expiration')),
            ]);
        }

        return $next($request);
    }
}
