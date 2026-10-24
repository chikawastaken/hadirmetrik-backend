<?php

namespace App\Modules\Identity\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class AccountSuspended extends ApiException
{
    public function __construct(string $message = 'Your account has been suspended. Please contact HR.')
    {
        parent::__construct($message, 403);
    }
}
