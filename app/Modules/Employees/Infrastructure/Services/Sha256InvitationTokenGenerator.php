<?php

namespace App\Modules\Employees\Infrastructure\Services;

use App\Modules\Employees\Contracts\InvitationTokenGenerator;
use Illuminate\Support\Str;

final class Sha256InvitationTokenGenerator implements InvitationTokenGenerator
{
    public function generate(): array
    {
        $plainToken = Str::random(32);

        return [
            'plain_token' => $plainToken,
            'token_hash' => $this->hash($plainToken),
        ];
    }

    public function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken.$this->pepper());
    }

    public function matches(string $plainToken, string $tokenHash): bool
    {
        return hash_equals($tokenHash, $this->hash($plainToken));
    }

    private function pepper(): string
    {
        return (string) config('app.key', 'hadirmetrik-pepper-default');
    }
}
