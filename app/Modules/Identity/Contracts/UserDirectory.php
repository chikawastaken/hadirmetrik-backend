<?php

namespace App\Modules\Identity\Contracts;

/**
 * Published contract: read-only lookups of user accounts for other modules.
 */
interface UserDirectory
{
    /**
     * @param  list<int>  $ids
     * @return array<int, string> user id => display name
     */
    public function namesByIds(array $ids): array;
}
