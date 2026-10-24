<?php

namespace App\Modules\Identity;

use App\Modules\Identity\Contracts\AccountRepository;
use App\Modules\Identity\Contracts\PasswordHasher;
use App\Modules\Identity\Contracts\TokenIssuer;
use App\Modules\Identity\Infrastructure\Services\EloquentAccountRepository;
use App\Modules\Identity\Infrastructure\Services\LaravelPasswordHasher;
use App\Modules\Identity\Infrastructure\Services\SanctumTokenIssuer;
use Illuminate\Support\ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepository::class, EloquentAccountRepository::class);
        $this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);
        $this->app->bind(TokenIssuer::class, SanctumTokenIssuer::class);
    }
}
