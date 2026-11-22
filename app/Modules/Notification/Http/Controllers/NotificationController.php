<?php

namespace App\Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notification\Infrastructure\Models\Notification;
use App\Shared\Http\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    public function index(Request $request): ApiResponse
    {
        $user = $request->user();
        $cursor = $request->filled('cursor') ? (int) $request->query('cursor') : null;
        $limit = min($request->integer('limit', 50), 100);

        $query = Notification::where('user_id', $user->id)
            ->latest('created_at');

        if ($cursor !== null) {
            $query->where('created_at', '<', (string) $cursor);
        }

        $notifications = $query->take($limit)->get();
        $unread = Notification::where('user_id', $user->id)->whereNull('read_at')->count();

        return ApiResponse::success([
            'notifications' => $notifications->map(fn (Notification $n): array => [
                'id' => $n->id,
                'category' => $n->category,
                'title' => $n->title,
                'body' => $n->body,
                'read_at' => $n->read_at?->toIso8601String(),
                'push_status' => $n->push_status,
                'created_at' => $n->created_at?->toIso8601String(),
            ])->values()->all(),
            'unread_count' => $unread,
        ]);
    }

    public function readOne(Request $request, int $id): ApiResponse
    {
        $notification = Notification::where('user_id', $request->user()->id)->find($id);

        if ($notification === null) {
            throw new ModelNotFoundException;
        }

        $notification->update(['read_at' => now()]);

        return ApiResponse::success([
            'notification' => [
                'id' => $notification->id,
                'read_at' => $notification->read_at?->toIso8601String(),
            ],
        ]);
    }

    public function readAll(Request $request): ApiResponse
    {
        $updated = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success([
            'updated' => $updated,
        ]);
    }
}
