<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Logout\LogoutUseCase;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class LogoutController extends Controller
{
    public function __invoke(Request $request, LogoutUseCase $useCase): ApiResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        $useCase->execute(
            $token instanceof PersonalAccessToken ? (string) $token->getKey() : null,
            (int) $user->getAuthIdentifier(),
        );

        return ApiResponse::success(null, 'Logout berhasil.');
    }
}
