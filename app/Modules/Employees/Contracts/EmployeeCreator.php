<?php

namespace App\Modules\Employees\Contracts;

use App\Modules\Employees\Contracts\Data\CreateEmployeeInput;
use App\Modules\Employees\Contracts\Data\CreateEmployeeResult;

interface EmployeeCreator
{
    public function create(CreateEmployeeInput $input, ?int $actorUserId = null): CreateEmployeeResult;
}
