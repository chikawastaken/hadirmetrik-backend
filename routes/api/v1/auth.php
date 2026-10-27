<?php

use App\Modules\Identity\Http\Controllers\ChangePasswordController;
use App\Modules\Identity\Http\Controllers\LoginController;
use App\Modules\Identity\Http\Controllers\LogoutController;
use App\Modules\Identity\Http\Controllers\MeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Authentication endpoints for mobile clients.
| Public routes: login
| Protected routes: me, change-password, logout
|
| The password-change guard deliberately does not apply here: a user with a
| temporary password must be able to read /me, change it, and log out.
|
*/

Route::post('/login', LoginController::class);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', MeController::class);
    Route::post('/auth/change-password', ChangePasswordController::class);
    Route::post('/logout', LogoutController::class);
});
