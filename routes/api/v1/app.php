<?php

// App routes — placeholder for Phase 3+
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'password.changed'])->group(function (): void {
    Route::get('/me/profile', [\App\Modules\Employees\Http\Controllers\EmployeeProfileController::class, 'show']);
    Route::patch('/me/profile', [\App\Modules\Employees\Http\Controllers\EmployeeProfileController::class, 'update']);

    // Phase 3 — Presensi Inti
    Route::get('/clock', [\App\Modules\Attendance\Http\Controllers\ClockController::class, 'index']);
    Route::post('/clock-in', [\App\Modules\Attendance\Http\Controllers\ClockController::class, 'in']);
    Route::post('/clock-out', [\App\Modules\Attendance\Http\Controllers\ClockController::class, 'out']);
    Route::get('/schedule', [\App\Modules\Attendance\Http\Controllers\ScheduleController::class, 'index']);
    Route::get('/history', [\App\Modules\Attendance\Http\Controllers\ClockController::class, 'index']);
    Route::patch('/history/{id}/correction', [\App\Modules\Attendance\Http\Controllers\HistoryController::class, 'correct']);

    // Phase 4 — Verifikasi Wajah & Offline Sync
    Route::post('/face/enroll', [\App\Modules\Attendance\Http\Controllers\FaceEnrollmentController::class, 'store']);
    Route::get('/face/enrollments/{id}', [\App\Modules\Attendance\Http\Controllers\FaceEnrollmentController::class, 'status']);
    Route::get('/face/templates', [\App\Modules\Attendance\Http\Controllers\FaceEnrollmentController::class, 'index']);
    Route::post('/face/templates/{id}/revoke', [\App\Modules\Attendance\Http\Controllers\FaceEnrollmentController::class, 'revoke']);
    Route::delete('/face/templates/{id}', [\App\Modules\Attendance\Http\Controllers\FaceEnrollmentController::class, 'destroy']);

    Route::post('/sync/attendance', [\App\Modules\Attendance\Http\Controllers\AttendanceSyncController::class, 'store']);
    Route::get('/sync/attendance/{id}', [\App\Modules\Attendance\Http\Controllers\AttendanceSyncController::class, 'show']);

    // Phase 5 — Manajemen Tugas
    Route::get('/tasks', [\App\Modules\Tasks\Http\Controllers\TaskController::class, 'index']);
    Route::get('/tasks/{id}', [\App\Modules\Tasks\Http\Controllers\TaskController::class, 'show'])->whereNumber('id');
    Route::post('/tasks/{id}/start', [\App\Modules\Tasks\Http\Controllers\TaskController::class, 'start'])->whereNumber('id');
    Route::patch('/tasks/{id}/progress', [\App\Modules\Tasks\Http\Controllers\TaskController::class, 'progress'])->whereNumber('id');
    Route::post('/tasks/{id}/submissions', [\App\Modules\Tasks\Http\Controllers\TaskController::class, 'submit'])->whereNumber('id');
    Route::post('/tasks/{id}/comments', [\App\Modules\Tasks\Http\Controllers\TaskCommentController::class, 'store'])->whereNumber('id');
});
