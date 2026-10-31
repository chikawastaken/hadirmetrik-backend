<?php
namespace App\Modules\Attendance\Http\Controllers;
use Illuminate\Http\Request;
use App\Shared\Http\ApiResponse;
class ScheduleController extends Controller
{
    public function index(Request $request): ApiResponse
    {
        return ApiResponse::success([
            'shift_name' => 'Shift Pagi',
            'start' => '08:00',
            'end' => '17:00',
            'days' => ['Senin','Selasa','Rabu','Kamis','Jumat'],
        ]);
    }
}
