<?php
namespace App\Modules\Attendance\Http\Controllers;
use Illuminate\Http\Request;
use App\Shared\Http\ApiResponse;
class ScheduleController extends Controller
{
    public function index(Request $request): ApiResponse
    {
        $user = $request->user();
        $shifts = \App\Modules\Employees\Infrastructure\Models\Shift::where('is_active', true)
            ->select('id', 'name', 'start_time', 'end_time', 'effective_from')
            ->get();
        return ApiResponse::success([
            'shifts' => $shifts,
            'user_id' => $user?->id,
        ]);
    }
}
