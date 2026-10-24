<?php

namespace App\Modules\Identity\Infrastructure\Services;

use App\Modules\Identity\Contracts\AccountRepository;
use App\Modules\Identity\Contracts\Data\Account;
use App\Modules\Identity\Contracts\Data\AccountCredentials;
use App\Modules\Identity\Infrastructure\Models\User;

final class EloquentAccountRepository implements AccountRepository
{
    public function findById(int $id): ?Account
    {
        $user = User::query()->find($id);

        return $user === null ? null : $this->toAccount($user);
    }

    public function findCredentialsByEmail(string $email): ?AccountCredentials
    {
        $user = User::query()->where('email', $email)->first();

        return $user === null
            ? null
            : new AccountCredentials($this->toAccount($user), $user->password);
    }

    public function existsByEmail(string $email): bool
    {
        return User::query()->where('email', $email)->exists();
    }

    public function create(string $name, string $email, string $passwordHash, string $role): Account
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'role' => $role,
        ]);

        return $this->toAccount($user);
    }

    private function toAccount(User $user): Account
    {
        return new Account($user->id, $user->name, $user->email, $user->role);
    }
}
