<?php

namespace App\Modules\Audit\Contracts;

/**
 * Published contract: append-only record of consequential business actions.
 * References: docs/architecture.md §6.4 (audit_logs), ADR 0008.
 */
interface AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $beforeSnapshot
     * @param  array<string, mixed>|null  $afterSnapshot
     */
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
    ): void;
}
