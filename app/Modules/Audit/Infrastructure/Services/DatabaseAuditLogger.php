<?php

namespace App\Modules\Audit\Infrastructure\Services;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Audit\Infrastructure\Models\AuditLog;

final class DatabaseAuditLogger implements AuditLogger
{
    public function log(
        string $entityType,
        ?string $entityId,
        string $action,
        ?array $beforeSnapshot = null,
        ?array $afterSnapshot = null,
        ?string $reason = null,
        ?int $actorUserId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        AuditLog::query()->create([
            'actor_user_id' => $actorUserId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'before_snapshot' => $beforeSnapshot,
            'after_snapshot' => $afterSnapshot,
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'recorded_at' => now(),
        ]);
    }
}
