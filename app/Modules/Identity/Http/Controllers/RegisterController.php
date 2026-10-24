<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\RegisterEmployee\RegisterEmployeeInput;
use App\Modules\Identity\Application\RegisterEmployee\RegisterEmployeeUseCase;
use App\Modules\Identity\Http\AuthPayload;
use App\Modules\Identity\Http\Requests\RegisterRequest;
use App\Shared\Http\ApiResponse;

final class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterEmployeeUseCase $useCase): ApiResponse
    {
        $result = $useCase->execute(new RegisterEmployeeInput(
            (string) $request->string('employee_number'),
            (string) $request->string('work_email'),
            (string) $request->string('password'),
            (string) $request->string('invitation_code'),
        ));

        return ApiResponse::success(
            AuthPayload::from($result),
            'Registrasi berhasil. Silakan gunakan token untuk akses selanjutnya.',
        );
    }
}
