<?php

namespace App\Modules\Employees\Infrastructure\Services;

use App\Modules\Employees\Contracts\InvitationDeliveryDispatcher;
use App\Modules\Employees\Infrastructure\Jobs\SendInvitationDelivery;

final class QueueInvitationDeliveryDispatcher implements InvitationDeliveryDispatcher
{
    public function __construct(
        private readonly InvitationUrlResolver $urls,
    ) {}

    public function dispatch(int $deliveryId, string $plainToken): void
    {
        // Resolve the link while the originating request is still available:
        // the queued job runs later, without the client's Host header.
        SendInvitationDelivery::dispatch(
            $deliveryId,
            $plainToken,
            $this->urls->base(),
        )->afterCommit();
    }
}
