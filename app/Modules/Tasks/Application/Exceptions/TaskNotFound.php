<?php

namespace App\Modules\Tasks\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class TaskNotFound extends ApiException
{
    public function __construct(string $message = 'Tugas tidak ditemukan.')
    {
        parent::__construct($message, 404);
    }
}
