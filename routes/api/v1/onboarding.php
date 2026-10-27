<?php

// Onboarding routes — employee-side invitation and face enrollment flows.
use App\Modules\Employees\Http\Controllers\InvitationAcceptanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'password.changed'])->group(function (): void {
    Route::post('invitations/{token}/accept', [InvitationAcceptanceController::class, 'accept'])
        ->where('token', '[A-Za-z0-9]{16,64}');
});
