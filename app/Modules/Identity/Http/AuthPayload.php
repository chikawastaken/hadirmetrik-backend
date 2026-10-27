<?php

namespace App\Modules\Identity\Http;

use App\Modules\Employees\Contracts\Data\EmployeeSummary;
use App\Modules\Identity\Contracts\Data\Account;
use App\Modules\Identity\Contracts\Data\AuthResult;

/**
 * Shapes identity data for the API contract (see docs/openapi/auth.yaml).
 */
final class AuthPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function from(AuthResult $result): array
    {
        return [
            'token' => $result->token->token,
            'token_type' => $result->token->type,
            'must_change_password' => $result->account->mustChangePassword,
            'user' => self::user($result->account),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function user(Account $account, ?EmployeeSummary $employee = null): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'email' => $account->email,
            'role' => $account->role,
            'must_change_password' => $account->mustChangePassword,
            'employee_number' => $employee?->employeeNumber,
            'employee' => self::employee($employee),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function employee(?EmployeeSummary $employee): ?array
    {
        if ($employee === null) {
            return null;
        }

        return [
            'id' => $employee->id,
            'employee_number' => $employee->employeeNumber,
            'full_name' => $employee->name,
            'work_email' => $employee->workEmail,
            'job_title' => $employee->jobTitle,
            'division' => $employee->divisionId === null ? null : [
                'id' => $employee->divisionId,
                'name' => $employee->divisionName,
            ],
            'employment_status' => $employee->employmentStatus,
            'face_registered' => $employee->faceRegisteredAt !== null,
            'face_registered_at' => $employee->faceRegisteredAt,
        ];
    }
}
