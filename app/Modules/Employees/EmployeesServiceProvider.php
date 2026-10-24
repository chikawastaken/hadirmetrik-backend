<?php

namespace App\Modules\Employees;

use App\Modules\Employees\Application\CreateEmployee\CreateEmployeeUseCase;
use App\Modules\Employees\Contracts\EmployeeCreator;
use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Employees\Contracts\EmployeeNumberGenerator;
use App\Modules\Employees\Contracts\InvitationCodeConsumer;
use App\Modules\Employees\Contracts\InvitationTokenGenerator;
use App\Modules\Employees\Contracts\TemporaryPasswordGenerator;
use App\Modules\Employees\Contracts\UserAccountProvisioner;
use App\Modules\Employees\Infrastructure\Services\DatabaseEmployeeNumberGenerator;
use App\Modules\Employees\Infrastructure\Services\DatabaseInvitationCodeConsumer;
use App\Modules\Employees\Infrastructure\Services\EloquentEmployeeDirectory;
use App\Modules\Employees\Infrastructure\Services\IdentityUserAccountProvisioner;
use App\Modules\Employees\Infrastructure\Services\RandomTemporaryPasswordGenerator;
use App\Modules\Employees\Infrastructure\Services\Sha256InvitationTokenGenerator;
use Illuminate\Support\ServiceProvider;

class EmployeesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmployeeDirectory::class, EloquentEmployeeDirectory::class);
        $this->app->bind(InvitationCodeConsumer::class, DatabaseInvitationCodeConsumer::class);

        $this->app->bind(EmployeeNumberGenerator::class, DatabaseEmployeeNumberGenerator::class);
        $this->app->bind(TemporaryPasswordGenerator::class, RandomTemporaryPasswordGenerator::class);
        $this->app->bind(InvitationTokenGenerator::class, Sha256InvitationTokenGenerator::class);
        $this->app->bind(UserAccountProvisioner::class, IdentityUserAccountProvisioner::class);
        $this->app->bind(EmployeeCreator::class, CreateEmployeeUseCase::class);
    }
}
