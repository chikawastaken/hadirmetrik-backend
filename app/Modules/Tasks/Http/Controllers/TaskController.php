<?php

namespace App\Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Application\Support\TaskAccess;
use App\Modules\Tasks\Http\Presenters\TaskPresenter;
use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAssignment;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;

final class TaskController extends Controller
{
    public function __construct(
        private readonly TaskAccess $access,
        private readonly TaskPresenter $presenter,
    ) {}

    public function index(Request $request): ApiResponse
    {
        $user = $request->user();
        $employeeId = $this->access->employeeIdFor((int) $user->getAuthIdentifier());

        if ($employeeId === null) {
            return ApiResponse::success(['tasks' => []]);
        }

        $assignments = TaskAssignment::query()
            ->with('task')
            ->where('employee_id', $employeeId)
            ->latest()
            ->get();

        return ApiResponse::success([
            'tasks' => $assignments->map(fn (TaskAssignment $assignment): array => [
                'assignment' => $this->presenter->assignment($assignment),
                'task' => $this->presenter->summary($assignment->task),
            ])->values()->all(),
        ]);
    }

    public function show(Request $request, int $id): ApiResponse
    {
        $task = Task::query()->find($id);
        $user = $request->user();

        if ($task === null || ! $this->access->canView(
            (int) $user->getAuthIdentifier(),
            (string) $user->role,
            $task,
        )) {
            return ApiResponse::error('Tugas tidak ditemukan.', 404);
        }

        return ApiResponse::success(['task' => $this->presenter->detail($task)]);
    }
}
