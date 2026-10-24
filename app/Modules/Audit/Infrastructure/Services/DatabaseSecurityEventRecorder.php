<?php

namespace App\Modules\Audit\Infrastructure\Services;

use App\Modules\Audit\Contracts\SecurityEventRecorder;
use App\Modules\Audit\Infrastructure\Models\SecurityEvent;

final class DatabaseSecurityEventRecorder implements SecurityEventRecorder
{
    public function record(string $event, array $context = [], ?int $actorUserId = null): void
    {
        SecurityEvent::query()->create([
            'event' => $event,
            'actor_user_id' => $actorUserId,
            'context' => $context,
        ]);
    }
}
