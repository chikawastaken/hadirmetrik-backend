<?php

namespace App\Modules\Employees\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class EmployeeAccountMissing extends ApiException
{
    public function __construct(string $message = 'Karyawan belum memiliki akun.')
    {
        parent::__construct($message, 422, [
            'employee' => [$message],
        ]);
    }
}
