<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Identity\Application\GetAuthenticatedUser\GetAuthenticatedUserUseCase;
use App\Modules\Identity\Http\AuthPayload;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;

final class MeController extends Controller
{
    public function __invoke(
        Request $request,
        GetAuthenticatedUserUseCase $useCase,
        EmployeeDirectory $employees,
    ): ApiResponse {
        $account = $useCase->execute((int) $request->user()->getAuthIdentifier());

        return ApiResponse::success(AuthPayload::user(
            $account,
            $employees->findByUserId($account->id),
        ));
    }
}
