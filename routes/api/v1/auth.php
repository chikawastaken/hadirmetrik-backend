<?php

use App\Modules\Identity\Http\Controllers\LoginController;
use App\Modules\Identity\Http\Controllers\LogoutController;
use App\Modules\Identity\Http\Controllers\MeController;
use App\Modules\Identity\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Authentication endpoints for mobile clients.
| Public routes: register, login
| Protected routes: me, logout
|
| Routes are centralized here (modules never load their own routes); each
| endpoint points at the owning module's controller.
|
*/

Route::post('/register', RegisterController::class);
Route::post('/login', LoginController::class);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', MeController::class);
    Route::post('/logout', LogoutController::class);
});
