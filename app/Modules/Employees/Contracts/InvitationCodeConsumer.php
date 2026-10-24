<?php

namespace App\Modules\Employees\Contracts;

/**
 * Published contract: consume one-time invitation codes.
 */
interface InvitationCodeConsumer
{
    public function isConsumable(string $code): bool;

    /**
     * Atomically mark the code as used. Returns false when the code does not
     * exist or was already consumed.
     */
    public function consume(string $code): bool;
}
