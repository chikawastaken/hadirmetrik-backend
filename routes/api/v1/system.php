<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| System Routes
|--------------------------------------------------------------------------
|
| Health and diagnostic endpoints — no authentication required.
|
*/

Route::get('/health', function () {
    $status = [
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
    ];

    try {
        DB::connection()->getPdo();
        $status['database'] = 'connected';
    } catch (Throwable $e) {
        $status['status'] = 'degraded';
        $status['database'] = 'error';
        $status['database_error'] = env('APP_DEBUG', false) ? $e->getMessage() : null;
    }

    $httpStatus = $status['status'] === 'healthy' ? 200 : 503;

    return response()->json($status, $httpStatus);
});
