<?php

namespace App\Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Application\ReviewSubmission\ReviewSubmissionUseCase;
use App\Modules\Tasks\Http\Presenters\TaskPresenter;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;

final class HrTaskSubmissionController extends Controller
{
    public function __construct(
        private readonly TaskPresenter $presenter,
    ) {}

    public function review(Request $request, int $id, ReviewSubmissionUseCase $useCase): ApiResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:approved,revision_requested'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $task = $useCase->execute(
            $id,
            (int) $user->getAuthIdentifier(),
            (string) $user->role,
            $validated['decision'],
            isset($validated['rating']) ? (int) $validated['rating'] : null,
            $validated['note'] ?? null,
            $request->ip(),
            $request->userAgent(),
        );

        return ApiResponse::success(
            ['task' => $this->presenter->detail($task)],
            'Penilaian tugas disimpan.',
        );
    }
}
