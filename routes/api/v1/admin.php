<?php

use App\Modules\Employees\Http\Controllers\HrEmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('hr')->group(function (): void {
    Route::post('employees', [HrEmployeeController::class, 'store']);
});
