<?php

namespace App\Modules\Tasks\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class TaskForbidden extends ApiException
{
    public function __construct(string $message = 'Anda tidak berwenang mengubah tugas ini.')
    {
        parent::__construct($message, 403);
    }
}
