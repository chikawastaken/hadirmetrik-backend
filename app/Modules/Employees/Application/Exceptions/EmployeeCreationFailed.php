<?php

namespace App\Modules\Employees\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class EmployeeCreationFailed extends ApiException
{
    public function __construct(string $field, string $message)
    {
        parent::__construct($message, 422, [$field => [$message]]);
    }
}
