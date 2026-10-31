<?php
namespace App\Modules\Attendance\Http\Controllers;
use Illuminate\Http\Request;
use App\Shared\Http\ApiResponse;
class ClockController extends Controller
{
    public function in(Request $request): ApiResponse
    {
        $user = $request->user();
        return ApiResponse::success([
            'clock_in' => now()->toIso8601String(),
            'user_id' => $user?->id,
        ]);
    }
    public function out(Request $request): ApiResponse
    {
        return ApiResponse::success([
            'clock_out' => now()->toIso8601String(),
        ]);
    }
}
