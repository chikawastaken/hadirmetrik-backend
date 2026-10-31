<?php
namespace App\Modules\Attendance\Http\Controllers;
use Illuminate\Http\Request;
use App\Shared\Http\ApiResponse;
class HistoryController extends Controller
{
    public function index(Request $request): ApiResponse
    {
        return ApiResponse::success([
            ['date'=>'2026-10-30','clock_in'=>'07:52','clock_out'=>'17:05','status'=>'lulus'],
            ['date'=>'2026-10-29','clock_in'=>'08:15','clock_out'=>'17:20','status'=>'terlambat'],
        ]);
    }
    public function correct(Request $request, $id): ApiResponse
    {
        return ApiResponse::success([
            'corrected' => (int)$id,
            'status' => 'pending_review',
        ]);
    }
}
