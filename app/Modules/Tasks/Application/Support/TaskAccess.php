<?php

namespace App\Modules\Tasks\Application\Support;

use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Tasks\Infrastructure\Models\Task;

/**
 * Object-level authorization for tasks.
 *
 * Authorization is deliberately small for the MVP: a task is visible to its
 * creator, to an assigned employee, and to HR/admin. Review is limited to the
 * creator or HR/admin.
 */
final class TaskAccess
{
    public function __construct(
        private readonly EmployeeDirectory $employees,
    ) {}

    public function employeeIdFor(int $userId): ?int
    {
        return $this->employees->findByUserId($userId)?->id;
    }

    public function canView(int $userId, string $role, Task $task): bool
    {
        if ($this->isHr($role)) {
            return true;
        }

        if ((int) $task->created_by === $userId) {
            return true;
        }

        $employeeId = $this->employeeIdFor($userId);

        return $employeeId !== null
            && $task->assignments()->where('employee_id', $employeeId)->exists();
    }

    public function canReview(int $userId, string $role, Task $task): bool
    {
        return $this->isHr($role) || (int) $task->created_by === $userId;
    }

    public function canCancel(int $userId, string $role, Task $task): bool
    {
        return $role === 'admin' || (int) $task->created_by === $userId;
    }

    public function isHr(string $role): bool
    {
        return in_array($role, ['hr', 'admin'], true);
    }
}
