<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EbookController;
use App\Http\Controllers\EbookShareController;
use App\Http\Controllers\SerpAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\HomeController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::get('/login', function () {
    if (auth()->check()) {
        return redirect('/home');
    }
    return view('auth.login');
})->name('login');

Route::post('/serp-login', [SerpAuthController::class, 'login'])
    ->name('serp.login');

Route::post('/logout', [SerpAuthController::class, 'logout'])
    ->name('logout');

// Local development fallback when SERP is unavailable.
if (app()->environment('local')) {
    Route::get('/dev-login', function () {
        $user = User::firstOrCreate(
            ['email' => 'local.admin@ebook.test'],
            [
                'name' => 'Local Admin',
                'serp_id' => 'LOCAL_ADMIN',
                'role' => 'admin',
                'status' => 'active',
                'created_by' => null,
            ]
        );

        if ($user->role !== 'admin') {
            $user->role = 'admin';
            $user->status = 'active';
            $user->save();
        }

        Auth::guard('web')->login($user);
        session([
            'serp_token' => 'local-dev-token',
            'serp_refresh' => 'local-dev-refresh',
            'serp_expiry' => now()->addDays(7)->toDateTimeString(),
        ]);

        return redirect('/home');
    })->name('dev.login');
}


/*
|--------------------------------------------------------------------------
| Protected (All Logged Users)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth','serp.auth','nocache'])->group(function () {

    Route::get('/home', [HomeController::class, 'userHome']);

    /* ---------------- Dashboard ---------------- */

    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])
        ->middleware('admin')
        ->name('admin.dashboard');
        
    Route::get('/get-subcategories/{id}', function ($id) {
        return \App\Models\Category::where('parent_id', $id)->get();
    });


    Route::get('/ebook/{slug}', [EbookController::class, 'view'])
    ->name('ebook.view');

    /* ---------------- Upload ---------------- */

    Route::post('/ebooks/upload',
        [EbookController::class, 'store']
    )->middleware('can.upload');


    /* ---------------- Share ---------------- */

    Route::post('/ebooks/share/{id}',
        [EbookShareController::class, 'generate']
    )->middleware('can.share');


    /* ---------------- Delete ---------------- */

    Route::delete('/ebook/delete/{id}',
        [EbookController::class, 'delete']
    );

});


/*
|--------------------------------------------------------------------------
| Admin Only
|--------------------------------------------------------------------------
*/

Route::middleware(['auth','admin'])->group(function () {

    Route::get('/admin/ebooks', [AdminController::class, 'ebooks'])
        ->name('admin.ebooks');

    Route::get('/admin/today-uploads', [AdminController::class, 'todayUploads'])
        ->name('admin.todayUploads');

    Route::get('/admin/categories', [AdminController::class, 'categories'])
        ->name('admin.categories');

    Route::post('/admin/categories/store-tree', [AdminController::class, 'storeCategoryTree'])
        ->name('admin.categories.storeTree');

    Route::put('/admin/categories/{id}', [AdminController::class, 'updateCategory'])
        ->name('admin.categories.update');

    Route::delete('/admin/categories/{id}', [AdminController::class, 'deleteCategory'])
        ->name('admin.categories.delete');

    Route::post('/admin/users/{id}/update',
        [UserController::class, 'update']
    )->name('admin.users.update');

    Route::delete('/admin/users/{id}',
        [UserController::class, 'destroy']
    )->name('admin.users.destroy');

    Route::post('/admin/users/{id}/reset-uploads',
        [AdminController::class, 'resetUserUploads']
    )->name('admin.users.resetUploads');

    Route::post('/admin/users/{id}/reset-shares',
        [AdminController::class, 'resetUserShares']
    )->name('admin.users.resetShares');

});


/*
|--------------------------------------------------------------------------
| Public Share View (No Login)
|--------------------------------------------------------------------------
*/

Route::get('/flip-book/{token}',
    [EbookShareController::class, 'view']
)->name('ebook.share');
