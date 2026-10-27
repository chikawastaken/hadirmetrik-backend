<?php

namespace App\Modules\Employees\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * HR endpoints are limited to the hr/admin roles (docs/architecture.md §7.1).
 */
final class EnsureHrRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! in_array($user->role, ['hr', 'admin'], true)) {
            throw new AccessDeniedHttpException;
        }

        return $next($request);
    }
}
