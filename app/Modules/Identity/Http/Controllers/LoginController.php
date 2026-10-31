<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Login\LoginInput;
use App\Modules\Identity\Application\Login\LoginUseCase;
use App\Modules\Identity\Http\Requests\LoginRequest;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\AuthPayload;

final class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, LoginUseCase $useCase): ApiResponse
    {
        $identifier = (string) ($request->input('identifier') ?? $request->input('email'));

        $result = $useCase->execute(new LoginInput(
            $identifier,
            (string) $request->string('password'),
            (string) ($request->input('device_name') ?? $request->userAgent() ?? 'unknown'),
        ));

        return ApiResponse::success(AuthPayload::from($result), 'Login berhasil.');
    }
}
