<?php

namespace App\Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Contracts\UserDirectory;
use App\Modules\Tasks\Application\Support\TaskAccess;
use App\Modules\Tasks\Http\Presenters\TaskPresenter;
use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskComment;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;

final class TaskCommentController extends Controller
{
    public function __construct(
        private readonly TaskAccess $access,
        private readonly TaskPresenter $presenter,
        private readonly UserDirectory $users,
    ) {}

    public function store(Request $request, int $id): ApiResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $task = Task::query()->find($id);

        if ($task === null || ! $this->access->canView(
            (int) $user->getAuthIdentifier(),
            (string) $user->role,
            $task,
        )) {
            return ApiResponse::error('Tugas tidak ditemukan.', 404);
        }

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'author_user_id' => $user->getAuthIdentifier(),
            'body' => $validated['body'],
        ]);

        $names = $this->users->namesByIds([(int) $comment->author_user_id]);

        return ApiResponse::success(
            ['comment' => $this->presenter->comment($comment, $names)],
            'Komentar ditambahkan.',
            201,
        );
    }
}
