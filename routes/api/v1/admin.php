<?php

use App\Modules\Employees\Http\Controllers\HrEmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'password.changed', 'hr.role'])->prefix('hr')->group(function (): void {
    Route::get('employees/create-options', [HrEmployeeController::class, 'options']);
    Route::get('employees/{id}', [HrEmployeeController::class, 'show'])->whereNumber('id');
    Route::post('employees/{id}/reset-password', [HrEmployeeController::class, 'resetPassword'])->whereNumber('id');
    Route::post('employees', [HrEmployeeController::class, 'store']);
});
