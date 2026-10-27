<?php

namespace App\Modules\Employees\Contracts;

interface InvitationTokenGenerator
{
    /**
     * @return array{plain_token: string, token_hash: string}
     */
    public function generate(): array;

    /**
     * Hash a plain token with the configured pepper so it can be looked up.
     */
    public function hash(string $plainToken): string;

    /**
     * Constant-time comparison between a plain token and a stored hash
     * (docs/architecture.md §5.6, §12.6).
     */
    public function matches(string $plainToken, string $tokenHash): bool;
}
