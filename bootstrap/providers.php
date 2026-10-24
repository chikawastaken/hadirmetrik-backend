<?php

use App\Modules\Audit\AuditServiceProvider;
use App\Modules\Employees\EmployeesServiceProvider;
use App\Modules\Identity\IdentityServiceProvider;
use App\Providers\AppServiceProvider;
use App\Shared\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    EmployeesServiceProvider::class,
    AuditServiceProvider::class,
    IdentityServiceProvider::class,
];
