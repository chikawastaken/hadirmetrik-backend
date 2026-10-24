<?php

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Contracts\Data\Account;
use App\Modules\Identity\Contracts\Data\AuthResult;

/**
 * Shapes identity data for the API contract (see docs/openapi/auth.yaml).
 */
final class AuthPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function from(AuthResult $result): array
    {
        return [
            'token' => $result->token->token,
            'token_type' => $result->token->type,
            'must_change_password' => $result->account->mustChangePassword,
            'user' => self::user($result->account),
        ];
    }

    /**
     * @return array{id: int, name: string, email: string}
     */
    public static function user(Account $account): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'email' => $account->email,
        ];
    }
}
