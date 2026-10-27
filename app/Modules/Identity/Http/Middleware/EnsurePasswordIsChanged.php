<?php

namespace App\Modules\Identity\Http\Middleware;

use App\Modules\Identity\Application\Exceptions\PasswordChangeRequired;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks every protected feature until a user with a temporary password has
 * replaced it (docs/architecture.md §5.9). Auth routes (/me, change-password,
 * logout) are intentionally left outside this guard.
 */
final class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && (bool) $user->must_change_password) {
            throw new PasswordChangeRequired;
        }

        return $next($request);
    }
}
