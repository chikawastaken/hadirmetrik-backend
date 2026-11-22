<?php

// App routes — placeholder for Phase 3+
use App\Modules\Attendance\Http\Controllers\AttendanceSyncController;
use App\Modules\Attendance\Http\Controllers\ClockController;
use App\Modules\Attendance\Http\Controllers\FaceEnrollmentController;
use App\Modules\Attendance\Http\Controllers\HistoryController;
use App\Modules\Attendance\Http\Controllers\ScheduleController;
use App\Modules\Employees\Http\Controllers\EmployeeProfileController;
use App\Modules\Leave\Http\Controllers\AdministrativeRequestController;
use App\Modules\Tasks\Http\Controllers\TaskAttachmentController;
use App\Modules\Tasks\Http\Controllers\TaskCommentController;
use App\Modules\Tasks\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'password.changed'])->group(function (): void {
    Route::get('/me/profile', [EmployeeProfileController::class, 'show']);
    Route::patch('/me/profile', [EmployeeProfileController::class, 'update']);

    // Phase 3 — Presensi Inti
    Route::get('/clock', [ClockController::class, 'index']);
    Route::post('/clock-in', [ClockController::class, 'in']);
    Route::post('/clock-out', [ClockController::class, 'out']);
    Route::get('/schedule', [ScheduleController::class, 'index']);
    Route::get('/history', [ClockController::class, 'index']);
    Route::patch('/history/{id}/correction', [HistoryController::class, 'correct']);

    // Phase 4 — Verifikasi Wajah & Offline Sync
    Route::post('/face/enroll', [FaceEnrollmentController::class, 'store']);
    Route::get('/face/enrollments/{id}', [FaceEnrollmentController::class, 'status']);
    Route::get('/face/templates', [FaceEnrollmentController::class, 'index']);
    Route::post('/face/templates/{id}/revoke', [FaceEnrollmentController::class, 'revoke']);
    Route::delete('/face/templates/{id}', [FaceEnrollmentController::class, 'destroy']);

    Route::post('/sync/attendance', [AttendanceSyncController::class, 'store']);
    Route::get('/sync/attendance/{id}', [AttendanceSyncController::class, 'show']);

    // Phase 5 — Manajemen Tugas
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{id}', [TaskController::class, 'show'])->whereNumber('id');
    Route::post('/tasks/{id}/start', [TaskController::class, 'start'])->whereNumber('id');
    Route::patch('/tasks/{id}/progress', [TaskController::class, 'progress'])->whereNumber('id');
    Route::post('/tasks/{id}/submissions', [TaskController::class, 'submit'])->whereNumber('id');
    Route::post('/tasks/{id}/comments', [TaskCommentController::class, 'store'])->whereNumber('id');
    Route::post('/tasks/{id}/attachments', [TaskAttachmentController::class, 'store'])->whereNumber('id');
    Route::get('/tasks/{id}/attachments/{attachmentId}', [TaskAttachmentController::class, 'download'])
        ->whereNumber('id')
        ->whereNumber('attachmentId');

    // Phase 6 — Cuti, Izin, dan Lembur
    Route::get('/administrative-requests/summary', [AdministrativeRequestController::class, 'summary']);
    Route::get('/administrative-requests', [AdministrativeRequestController::class, 'index']);
    Route::post('/administrative-requests', [AdministrativeRequestController::class, 'store']);
    Route::get('/administrative-requests/{id}', [AdministrativeRequestController::class, 'show'])->whereNumber('id');
    Route::post('/administrative-requests/{id}/cancel', [AdministrativeRequestController::class, 'cancel'])->whereNumber('id');
    Route::post('/administrative-requests/{id}/resubmit', [AdministrativeRequestController::class, 'resubmit'])->whereNumber('id');
});
