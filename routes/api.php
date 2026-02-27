<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JwtAuthController;
use App\Http\Controllers\EbookController;
use App\Http\Controllers\EbookShareController;

/*
|--------------------------------------------------------------------------
| Public APIs (No Login Required)
|--------------------------------------------------------------------------
*/

// JWT Auth
Route::post('/register', [JwtAuthController::class, 'register']);

Route::post('/login', [JwtAuthController::class, 'login']);
Route::post('/logout', [JwtAuthController::class, 'logout']);

// Public ebook preview
Route::get('/ebook/public/{id}', [EbookController::class, 'publicView']);

// ✅ Public share link
Route::get('/share/{token}', [EbookShareController::class, 'view']);


/*
|--------------------------------------------------------------------------
| Protected APIs (JWT Required)
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->group(function () {


    // Auth check
    Route::get('/auth-check', function () {
        return response()->json([
            'status' => true,
            'user' => auth()->user()
        ]);
    });

    // View ebook (API)
    Route::get('/ebook/view/{id}', [EbookController::class, 'viewApi']);


    /*
    |--------------------------------------------------------------------------
    | Admin Only
    |--------------------------------------------------------------------------
    */

    Route::middleware(['admin'])->group(function () {

        Route::post('/ebooks/upload', [EbookController::class, 'store']);

        Route::delete('/ebook/delete/{id}', [EbookController::class, 'delete']);

        // Generate share link
        Route::get('/ebook/share/{id}', [EbookShareController::class, 'generate']);
    });

});
