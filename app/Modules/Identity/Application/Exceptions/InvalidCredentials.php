<?php

namespace App\Modules\Identity\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class InvalidCredentials extends ApiException
{
    public function __construct(string $message = 'Kredensial tidak valid.')
    {
        parent::__construct($message, 401);
    }
}
