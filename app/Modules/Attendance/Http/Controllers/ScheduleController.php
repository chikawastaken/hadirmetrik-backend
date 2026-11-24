<?php
namespace App\Modules\Attendance\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Http\ApiResponse;
class ScheduleController extends Controller
{
    public function index(Request $request): ApiResponse
    {
        $user = $request->user();
        $shifts = \App\Modules\Employees\Infrastructure\Models\Shift::where('is_active', true)
            ->select('id', 'name', 'start_time', 'end_time')
            ->get();
        $employee = \App\Modules\Employees\Infrastructure\Models\Employee::where('user_id', $user?->id)->first();
        $today = now()->toDateString();
        $assignment = $employee === null ? null : \App\Modules\Employees\Infrastructure\Models\EmployeeShiftAssignment::with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $today)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $today))
            ->orderByDesc('effective_from')
            ->first();
        return ApiResponse::success([
            'shift' => $assignment === null ? null : [
                'id' => $assignment->shift->id,
                'name' => $assignment->shift->name,
                'start_time' => $assignment->shift->start_time,
                'end_time' => $assignment->shift->end_time,
            ],
            'shifts' => $shifts,
            'user_id' => $user?->id,
        ]);
    }
}
