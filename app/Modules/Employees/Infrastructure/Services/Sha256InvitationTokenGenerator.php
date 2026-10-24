<?php

namespace App\Modules\Employees\Infrastructure\Services;

use App\Modules\Employees\Contracts\InvitationTokenGenerator;
use Illuminate\Support\Str;

final class Sha256InvitationTokenGenerator implements InvitationTokenGenerator
{
    public function generate(): array
    {
        $plainToken = Str::random(32);
        $pepper = (string) config('app.key', 'hadirmetrik-pepper-default');
        $tokenHash = hash('sha256', $plainToken.$pepper);

        return [
            'plain_token' => $plainToken,
            'token_hash' => $tokenHash,
        ];
    }
}
