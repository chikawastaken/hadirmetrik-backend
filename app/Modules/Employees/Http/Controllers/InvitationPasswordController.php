<?php

namespace App\Modules\Employees\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Employees\Application\Invitations\SetPasswordFromInvitation;
use App\Modules\Employees\Http\Requests\SetPasswordFromInvitationRequest;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\AuthPayload;

final class InvitationPasswordController extends Controller
{
    public function store(
        string $token,
        SetPasswordFromInvitationRequest $request,
        SetPasswordFromInvitation $useCase,
    ): ApiResponse {
        $result = $useCase->execute(
            $token,
            (string) $request->string('new_password'),
            (string) ($request->userAgent() ?? 'unknown'),
        );

        return ApiResponse::success(
            AuthPayload::from($result),
            'Kata sandi berhasil dibuat. Lanjutkan registrasi wajah.',
        );
    }
}
