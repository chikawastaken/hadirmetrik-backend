<?php

// App routes — placeholder for Phase 3+
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'password.changed'])->group(function (): void {
    Route::get('/me/profile', [\App\Modules\Employees\Http\Controllers\EmployeeProfileController::class, 'show']);
    Route::patch('/me/profile', [\App\Modules\Employees\Http\Controllers\EmployeeProfileController::class, 'update']);

    // Phase 3 — Presensi Inti
    Route::post('/clock-in', [\App\Modules\Attendance\Http\Controllers\ClockController::class, 'in']);
    Route::post('/clock-out', [\App\Modules\Attendance\Http\Controllers\ClockController::class, 'out']);
    Route::get('/schedule', [\App\Modules\Attendance\Http\Controllers\ScheduleController::class, 'index']);
    Route::get('/history', [\App\Modules\Attendance\Http\Controllers\ClockController::class, 'index']);
    Route::patch('/history/{id}/correction', [\App\Modules\Attendance\Http\Controllers\HistoryController::class, 'correct']);
});
