<?php

namespace App\Modules\Employees\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Employees\Application\Invitations\RevokeInvitation;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;

final class HrInvitationController extends Controller
{
    public function destroy(
        int $id,
        Request $request,
        RevokeInvitation $useCase,
    ): ApiResponse {
        return ApiResponse::success(
            $useCase->execute($id, $request->user()?->id),
            'Undangan berhasil dicabut.',
        );
    }
}
