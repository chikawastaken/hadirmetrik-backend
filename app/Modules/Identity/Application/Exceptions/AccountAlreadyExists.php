<?php

namespace App\Modules\Identity\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class AccountAlreadyExists extends ApiException
{
    public function __construct(string $message = 'Akun dengan email ini sudah terdaftar.')
    {
        parent::__construct($message, 409);
    }
}
