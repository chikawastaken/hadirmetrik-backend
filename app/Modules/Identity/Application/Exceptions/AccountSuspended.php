<?php

namespace App\Modules\Identity\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class AccountSuspended extends ApiException
{
    public function __construct(string $message = 'Akun Anda dinonaktifkan. Silakan hubungi HR.')
    {
        parent::__construct($message, 403);
    }
}
