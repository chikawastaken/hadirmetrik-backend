<?php

namespace App\Modules\Employees\Application\ResetEmployeePassword;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Employees\Application\Exceptions\EmployeeAccountMissing;
use App\Modules\Employees\Contracts\TemporaryPasswordGenerator;
use App\Modules\Employees\Contracts\UserAccountProvisioner;
use App\Modules\Employees\Infrastructure\Models\Employee;

final class ResetEmployeePasswordUseCase
{
    public function __construct(
        private readonly TemporaryPasswordGenerator $passwords,
        private readonly UserAccountProvisioner $accounts,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(int $employeeId, ?int $actorUserId): array
    {
        $employee = Employee::query()->findOrFail($employeeId);

        if ($employee->user_id === null) {
            throw new EmployeeAccountMissing;
        }

        $initialPassword = $this->passwords->generate(16);

        $this->accounts->resetPassword($employee->user_id, $initialPassword);

        $this->auditLogger->log(
            entityType: 'employee',
            entityId: (string) $employee->id,
            action: 'employee.password_reset',
            afterSnapshot: [
                'user_id' => $employee->user_id,
                'must_change_password' => true,
            ],
            actorUserId: $actorUserId,
        );

        return [
            'employee_id' => $employee->id,
            'employee_number' => $employee->employee_number,
            'initial_password' => $initialPassword,
            'must_change_password' => true,
        ];
    }
}
