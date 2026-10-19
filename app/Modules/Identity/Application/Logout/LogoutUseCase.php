<?php

namespace App\Modules\Identity\Application\Logout;

use App\Modules\Audit\Contracts\SecurityEventRecorder;
use App\Modules\Identity\Contracts\TokenIssuer;

final class LogoutUseCase
{
    public function __construct(
        private readonly TokenIssuer $tokens,
        private readonly SecurityEventRecorder $events,
    ) {}

    public function execute(?string $tokenId, ?int $actorUserId): void
    {
        if ($tokenId !== null) {
            $this->tokens->revoke($tokenId);
        }

        $this->events->record('logout', [], $actorUserId);
    }
}
