<?php

namespace App\Modules\Identity\Infrastructure\Services;

use App\Modules\Identity\Contracts\Data\Account;
use App\Modules\Identity\Contracts\Data\IssuedToken;
use App\Modules\Identity\Contracts\TokenIssuer;
use App\Modules\Identity\Infrastructure\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

final class SanctumTokenIssuer implements TokenIssuer
{
    public function issue(Account $account, string $deviceName): IssuedToken
    {
        $user = User::query()->findOrFail($account->id);

        $minutes = (int) config('sanctum.expiration');
        $expiresAt = $minutes > 0 ? now()->addMinutes($minutes) : null;

        return new IssuedToken(
            $user->createToken($deviceName, ['*'], $expiresAt)->plainTextToken,
            'Bearer',
            $expiresAt?->toIso8601String(),
        );
    }

    public function revoke(string $tokenId): void
    {
        PersonalAccessToken::query()->whereKey($tokenId)->delete();
    }
}
