<?php
namespace App\Modules\Attendance\Http\Controllers;

use Illuminate\Http\Request;
use App\Shared\Http\ApiResponse;

class ClockController extends Controller
{
    public function in(Request $request): ApiResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::error('Tidak terautentikasi.', 401);
        }
        // Fase 3: validasi clock-in hanya dalam shift, tidak duplikat
        $today = now()->format('Y-m-d');
        $existing = \App\Modules\Attendance\Infrastructure\Models\ClockRecord::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->whereNotNull('clock_in')
            ->first();
        if ($existing) {
            return ApiResponse::error('Clock-in sudah tercatat hari ini.', 422);
        }
        $record = \App\Modules\Attendance\Infrastructure\Models\ClockRecord::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in' => now(),
            'clock_out' => null,
            'status' => 'pending_sync',
        ]);
        return ApiResponse::success([
            'clock_in' => $record->clock_in->toIso8601String(),
            'status' => $record->status,
        ]);
    }

    public function out(Request $request): ApiResponse
    {
        $user = $request->user();
        $today = now()->format('Y-m-d');
        $record = \App\Modules\Attendance\Infrastructure\Models\ClockRecord::where('user_id', $user?->id)
            ->whereDate('date', $today)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();
        if (! $record) {
            return ApiResponse::error('Tidak ada clock-in aktif hari ini.', 422);
        }
        $record->update([
            'clock_out' => now(),
            'status' => 'synced',
        ]);
        return ApiResponse::success([
            'clock_out' => $record->clock_out->toIso8601String(),
            'duration_minutes' => $record->clock_in->diffInMinutes($record->clock_out),
            'status' => $record->status,
        ]);
    }

    public function index(Request $request): ApiResponse
    {
        $user = $request->user();
        $records = \App\Modules\Attendance\Infrastructure\Models\ClockRecord::where('user_id', $user?->id)
            ->orderByDesc('date')
            ->limit(30)
            ->get();
        return ApiResponse::success($records->map(fn($r) => [
            'date' => $r->date,
            'clock_in' => $r->clock_in?->toIso8601String(),
            'clock_out' => $r->clock_out?->toIso8601String(),
            'status' => $r->status,
        ]));
    }
}
