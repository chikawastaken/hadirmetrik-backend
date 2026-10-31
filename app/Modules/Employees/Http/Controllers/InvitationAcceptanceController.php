<?php

namespace App\Modules\Employees\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Employees\Application\Invitations\AcceptInvitation;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;

final class InvitationAcceptanceController extends Controller
{
    public function accept(
        string $token,
        Request $request,
        AcceptInvitation $useCase,
    ): ApiResponse {
        return ApiResponse::success(
            $useCase->execute($token, (int) $request->user()?->id),
            'Undangan valid. Lanjutkan registrasi wajah.',
        );
    }
}
