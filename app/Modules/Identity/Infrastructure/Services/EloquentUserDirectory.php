<?php

namespace App\Modules\Identity\Infrastructure\Services;

use App\Modules\Identity\Contracts\UserDirectory;
use App\Modules\Identity\Infrastructure\Models\User;

final class EloquentUserDirectory implements UserDirectory
{
    public function namesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->map(fn (mixed $name): string => (string) $name)
            ->all();
    }
}
