<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\ChangePassword\ChangePasswordInput;
use App\Modules\Identity\Application\ChangePassword\ChangePasswordUseCase;
use App\Modules\Identity\Http\Requests\ChangePasswordRequest;
use App\Shared\Http\ApiResponse;

final class ChangePasswordController extends Controller
{
    public function __invoke(ChangePasswordRequest $request, ChangePasswordUseCase $useCase): ApiResponse
    {
        $useCase->execute(new ChangePasswordInput(
            (int) $request->user()->getAuthIdentifier(),
            (string) $request->string('current_password'),
            (string) $request->string('new_password'),
        ));

        return ApiResponse::success([
            'must_change_password' => false,
        ], 'Kata sandi berhasil diperbarui.');
    }
}
