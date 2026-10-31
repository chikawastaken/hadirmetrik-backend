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

    public function update(Request $request, GetEmployeeDetailUseCase $useCase): ApiResponse
    {
        $user = $request->user();
        $employee = $this->directory->findByUserId((int) $user->getAuthIdentifier());

        if ($employee === null) {
            return ApiResponse::error('Profil karyawan tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'phone_e164' => ['nullable', 'string', 'max:20', 'regex:/^\+[1-9]\d{1,14}$/'],
            'work_email' => ['nullable', 'email', 'max:255', 'lowercase'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'job_title' => ['nullable', 'string', 'max:100'],
        ]);

        $emp = \App\Modules\Employees\Infrastructure\Models\Employee::findOrFail($employee->id);

        if (isset($validated['work_email'])) {
            $existing = \App\Modules\Employees\Infrastructure\Models\Employee::where('work_email', $validated['work_email'])
                ->where('id', '!=', $emp->id)
                ->first();
            if ($existing) {
                return ApiResponse::error('Email sudah digunakan karyawan lain.', 422, ['work_email' => ['Email sudah digunakan karyawan lain.']]);
            }
        }

        if (isset($validated['division_id'])) {
            $div = \App\Modules\Employees\Infrastructure\Models\Division::where('id', $validated['division_id'])
                ->where('is_active', true)->first();
            if (!$div) {
                return ApiResponse::error('Divisi tidak aktif atau tidak ditemukan.', 422, ['division_id' => ['Divisi tidak aktif atau tidak ditemukan.']]);
            }
        }

        $emp->update(collect($validated)->only(['phone_e164', 'work_email', 'division_id', 'job_title'])->toArray());

        $updatedEmployee = $this->directory->findByUserId((int) $user->getAuthIdentifier());

        $profile = $useCase->execute($updatedEmployee ? $updatedEmployee->id : $emp->id);

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
