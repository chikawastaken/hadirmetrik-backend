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

    public function findCredentialsById(int $id): ?AccountCredentials
    {
        $user = User::query()->find($id);

        return $user === null
            ? null
            : new AccountCredentials($this->toAccount($user), $user->password);
    }

    public function create(
        string $name,
        string $email,
        string $passwordHash,
        string $role,
        string $status = 'active',
        bool $mustChangePassword = true,
    ): Account {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'role' => $role,
            'status' => $status,
            'must_change_password' => $mustChangePassword,
        ]);

        return $this->toAccount($user);
    }

    private function toAccount(User $user): Account
    {
        return new Account(
            $user->id,
            $user->name,
            $user->email,
            $user->role,
            $user->status,
            (bool) $user->must_change_password,
        );
    }

    public function touchLastLogin(int $id): void
    {
        User::query()->where('id', $id)->update(['last_login_at' => now()]);
    }

    public function updatePassword(int $id, string $passwordHash, bool $mustChangePassword = false): void
    {
        User::query()->whereKey($id)->update([
            'password' => $passwordHash,
            'must_change_password' => $mustChangePassword,
            'remember_token' => null,
        ]);
    }
}
