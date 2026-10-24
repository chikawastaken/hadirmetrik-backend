<?php

namespace App\Modules\Audit\Contracts;

/**
 * Published contract: append-only record of security-relevant events.
 */
interface SecurityEventRecorder
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(string $event, array $context = [], ?int $actorUserId = null): void;
}
