<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JwtAuthenticate;

use App\Http\Middleware\AdminMiddleware;
// use App\Http\Middleware\SerpAuth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
   ->withMiddleware(function (Middleware $middleware): void {

    // Disable CSRF for JWT routes
    $middleware->validateCsrfTokens(except: [
          'api/*',
    'web-login',
    'web-logout',
    'serp-login',
    'logout',
    ]);

   $middleware->alias([

    'jwt.auth'  => Tymon\JWTAuth\Http\Middleware\Authenticate::class,
    'admin'     => App\Http\Middleware\AdminMiddleware::class,
    'web.auth'  => App\Http\Middleware\WebAuthMiddleware::class,
    'guest.jwt' => App\Http\Middleware\GuestMiddleware::class,
    'nocache'   => App\Http\Middleware\NoCache::class,
'admin' => \App\Http\Middleware\AdminMiddleware::class,
'can.upload' => \App\Http\Middleware\CanUpload::class,
'can.share' => \App\Http\Middleware\CanShare::class,


    // SERP Auto Refresh Middleware (ONLY ONE)
    'serp.auth' => \App\Http\Middleware\SerpRefreshMiddleware::class,

]);

   // Apply no-cache headers to all web routes to prevent BFCache
   $middleware->appendToGroup('web', \App\Http\Middleware\NoCache::class);

}) 



  
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
