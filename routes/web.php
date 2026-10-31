<?php

use App\Modules\Employees\Http\Controllers\InvitationBridgeController;
use App\Modules\Identity\Http\Controllers\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
        'health' => url('/api/v1/health'),
    ]);
});
Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware('signed')
    ->name('verification.verify');

// Clickable bridge from the invitation email to the app's custom scheme.
Route::get('/invitations/{token}', InvitationBridgeController::class)
    ->where('token', '[A-Za-z0-9]{16,64}')
    ->name('invitations.bridge');
