<?php
namespace App\Modules\Attendance\Http\Controllers;
use Illuminate\Http\Request;
use App\Shared\Http\ApiResponse;
class HistoryController extends Controller
{
    public function index(Request $request): ApiResponse
    {
        $user = $request->user();
        $records = \App\Modules\Attendance\Infrastructure\Models\ClockRecord::where('user_id', $user?->id)
            ->orderByDesc('date')
            ->limit(30)
            ->get();
        return ApiResponse::success($records);
    }

    public function correct(Request $request, $id): ApiResponse
    {
        $record = \App\Modules\Attendance\Infrastructure\Models\ClockRecord::find($id);
        if (! $record) {
            return ApiResponse::error('Data presensi tidak ditemukan.', 404);
        }
        $record->update([
            'status' => 'pending_review',
        ]);
        return ApiResponse::success([
            'corrected' => $record->id,
            'status' => $record->status,
        ]);
    }
}
