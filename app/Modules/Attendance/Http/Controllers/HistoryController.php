<?php
namespace App\Modules\Attendance\Http\Controllers;
use App\Http\Controllers\Controller;
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
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        // Scoped to the caller: another user's record is a 404, so ids cannot be probed.
        $record = \App\Modules\Attendance\Infrastructure\Models\ClockRecord::where('user_id', $request->user()->id)
            ->find($id);
        if (! $record) {
            return ApiResponse::error('Data presensi tidak ditemukan.', 404);
        }
        $record->update([
            'status' => 'pending_review',
            'correction_reason' => $validated['reason'],
        ]);
        return ApiResponse::success([
            'corrected' => $record->id,
            'status' => $record->status,
        ]);
    }
}
