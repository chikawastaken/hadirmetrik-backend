<?php

use App\Modules\Employees\Http\Controllers\HrEmployeeController;
use App\Modules\Employees\Http\Controllers\HrInvitationController;
use App\Modules\Tasks\Http\Controllers\HrTaskController;
use App\Modules\Tasks\Http\Controllers\HrTaskSubmissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'password.changed', 'hr.role'])->prefix('hr')->group(function (): void {
    Route::get('employees/create-options', [HrEmployeeController::class, 'options']);
    Route::get('employees/{id}', [HrEmployeeController::class, 'show'])->whereNumber('id');
    Route::post('employees/{id}/invitations', [HrEmployeeController::class, 'resendInvitation'])->whereNumber('id');
    Route::post('employees/{id}/reset-password', [HrEmployeeController::class, 'resetPassword'])->whereNumber('id');
    Route::post('employees', [HrEmployeeController::class, 'store']);
    Route::delete('invitations/{id}', [HrInvitationController::class, 'destroy'])->whereNumber('id');

    Route::get('tasks', [HrTaskController::class, 'index']);
    Route::get('tasks/options', [HrTaskController::class, 'options']);
    Route::post('tasks', [HrTaskController::class, 'store']);
    Route::post('tasks/{id}/cancel', [HrTaskController::class, 'cancel'])->whereNumber('id');
    Route::post('task-submissions/{id}/review', [HrTaskSubmissionController::class, 'review'])->whereNumber('id');
});
