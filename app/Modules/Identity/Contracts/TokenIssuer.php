<?php

namespace App\Modules\Identity\Contracts;

use App\Modules\Identity\Contracts\Data\Account;
use App\Modules\Identity\Contracts\Data\IssuedToken;

/**
 * Issues and revokes access tokens. Keeps the Sanctum implementation at the
 * edge so the use cases never depend on the auth framework.
 */
interface TokenIssuer
{
    public function issue(Account $account, string $deviceName): IssuedToken;

    public function revoke(string $tokenId): void;
}
