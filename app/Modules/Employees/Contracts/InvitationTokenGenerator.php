<?php

namespace App\Modules\Employees\Contracts;

interface InvitationTokenGenerator
{
    /**
     * @return array{plain_token: string, token_hash: string}
     */
    public function generate(): array;
}
