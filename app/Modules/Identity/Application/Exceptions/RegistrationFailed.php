<?php

namespace App\Modules\Identity\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class RegistrationFailed extends ApiException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(string $message, array $errors)
    {
        parent::__construct($message, 422, $errors);
    }
}
