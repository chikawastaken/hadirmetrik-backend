<?php

namespace App\Modules\Tasks\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class ReviewValidationFailed extends ApiException
{
    public function __construct(string $message, string $code)
    {
        parent::__construct($message, 422, ['code' => [$code]]);
    }
}
