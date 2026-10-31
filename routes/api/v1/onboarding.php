<?php

// Onboarding routes — employee-side invitation and face enrollment flows.
use App\Modules\Employees\Http\Controllers\InvitationAcceptanceController;
use App\Modules\Employees\Http\Controllers\InvitationPasswordController;
use Illuminate\Support\Facades\Route;

// The invitation link itself is the credential here: the employee has no
// session yet, and must_change_password is the flag this endpoint clears.
Route::post('invitations/{token}/password', [InvitationPasswordController::class, 'store'])
    ->where('token', '[A-Za-z0-9]{16,64}')
    ->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'password.changed'])->group(function (): void {
    Route::post('invitations/{token}/accept', [InvitationAcceptanceController::class, 'accept'])
        ->where('token', '[A-Za-z0-9]{16,64}');
});
