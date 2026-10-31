<?php

use App\Modules\Employees\Http\Middleware\EnsureHrRole;
use App\Modules\Identity\Http\Middleware\EnsurePasswordIsChanged;
use App\Shared\Exceptions\ApiException;
use App\Shared\Http\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi();

        $middleware->alias([
            'password.changed' => EnsurePasswordIsChanged::class,
            'hr.role' => EnsureHrRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $isApi = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        // Framework-level failures must use the same envelope as controller
        // responses so mobile clients only parse one error shape.
        $exceptions->render(function (ApiException $e, Request $request) use ($isApi) {
            return $isApi($request)
                ? ApiResponse::error($e->getMessage(), $e->status(), $e->errors() ?: null)
                : null;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            return $isApi($request)
                ? ApiResponse::error('Tidak terautentikasi.', 401)
                : null;
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            return $isApi($request)
                ? ApiResponse::error('Validasi gagal.', 422, $e->errors())
                : null;
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($isApi) {
            return $isApi($request)
                ? ApiResponse::error('Terlalu banyak percobaan. Silakan coba lagi nanti.', 429)
                : null;
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) use ($isApi) {
            return $isApi($request)
                ? ApiResponse::error('Data tidak ditemukan.', 404)
                : null;
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($isApi) {
            return $isApi($request)
                ? ApiResponse::error('Metode HTTP tidak diizinkan.', 405)
                : null;
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($isApi) {
            return $isApi($request)
                ? ApiResponse::error('Akses ditolak.', 403)
                : null;
        });
    })
    ->booted(function (): void {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip());
        });

        Authenticate::redirectUsing(function (Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            return route('login');
        });
    })
    ->create();
