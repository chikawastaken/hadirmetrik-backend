<?php

use App\Modules\Audit\AuditServiceProvider;
use App\Modules\Employees\EmployeesServiceProvider;
use App\Modules\Identity\IdentityServiceProvider;
use App\Modules\Leave\LeaveServiceProvider;
use App\Modules\Notification\NotificationServiceProvider;
use App\Modules\Tasks\TasksServiceProvider;
use App\Providers\AppServiceProvider;
use App\Shared\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    EmployeesServiceProvider::class,
    AuditServiceProvider::class,
    IdentityServiceProvider::class,
    TasksServiceProvider::class,
    LeaveServiceProvider::class,
    NotificationServiceProvider::class,
];
