<?php

namespace App\Modules\Notification\Http\Presenters;

use App\Modules\Notification\Infrastructure\Models\Notification;

final class NotificationPresenter
{
    public function notification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'category' => $notification->category,
            'title' => $notification->title,
            'body' => $notification->body,
            'read_at' => $notification->read_at?->toIso8601String(),
            'push_status' => $notification->push_status,
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
