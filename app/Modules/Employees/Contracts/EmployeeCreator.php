<?php

namespace App\Modules\Employees\Contracts;

use App\Modules\Employees\Contracts\Data\CreateEmployeeResult;
use App\Modules\Employees\Contracts\Data\CreateEmployeeInput;

interface EmployeeCreator
{
    public function create(CreateEmployeeInput $input, ?int $actorUserId = null): CreateEmployeeResult;
}
