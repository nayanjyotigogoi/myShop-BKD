<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

/*
|--------------------------------------------------------------------------
| Web Routes (Admin only)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::prefix('admin')->group(function () {

    Route::middleware('guest')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLoginForm'])
            ->name('admin.login');

        Route::post('login', [AdminAuthController::class, 'login'])
            ->name('admin.login.submit');
    });

    Route::middleware(['auth', 'super_admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])
            ->name('admin.logout');

        Route::get('/home', function () {
            return redirect()->route('admin.users.index');
        })->name('admin.dashboard');

        Route::resource('users', AdminUserController::class)
            ->except(['show'])
            ->names('admin.users');
    });
});
