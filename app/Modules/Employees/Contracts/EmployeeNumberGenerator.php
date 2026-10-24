<?php

namespace App\Modules\Employees\Contracts;

interface EmployeeNumberGenerator
{
    public function next(): string;
}
