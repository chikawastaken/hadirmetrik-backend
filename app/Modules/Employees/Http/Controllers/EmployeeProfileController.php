<?php

namespace App\Modules\Employees\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Employees\Application\GetEmployeeDetail\GetEmployeeDetailUseCase;
use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;

final class EmployeeProfileController extends Controller
{
    public function __construct(
        private readonly EmployeeDirectory $directory,
    ) {}

    public function show(Request $request, GetEmployeeDetailUseCase $useCase): ApiResponse
    {
        $user = $request->user();
        $employee = $this->directory->findByUserId((int) $user->getAuthIdentifier());

        if ($employee === null) {
            return ApiResponse::error('Profil karyawan tidak ditemukan.', 404);
        }

        $profile = $useCase->execute($employee->id);

        return ApiResponse::success([
            'employee' => $profile['employee'],
            'compensation' => $profile['compensation'],
            'shift_assignment' => $profile['shift_assignment'],
            'work_location_assignment' => $profile['work_location_assignment'],
            'invitation' => $profile['invitation'],
            'manager' => $profile['employee']['manager'] ?? null,
        ]);
    }
}
