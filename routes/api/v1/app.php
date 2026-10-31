<?php

// App routes — placeholder for Phase 3+
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'password.changed'])->group(function (): void {
    Route::get('/me/profile', [\App\Modules\Employees\Http\Controllers\EmployeeProfileController::class, 'show']);
    Route::patch('/me/profile', [\App\Modules\Employees\Http\Controllers\EmployeeProfileController::class, 'update']);
});
