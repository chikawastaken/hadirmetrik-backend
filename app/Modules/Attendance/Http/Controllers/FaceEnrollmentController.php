<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Http\Requests\StoreFaceEnrollmentRequest;
use App\Modules\Attendance\Infrastructure\Models\FaceEnrollmentRequest;
use App\Modules\Attendance\Infrastructure\Models\FaceTemplate;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;

class FaceEnrollmentController extends Controller
{
    /**
     * Submit a face enrollment attempt.
     *
     * Until a face model/provider is wired in, the attempt stays `pending`.
     * The template is versioned per user and never holds raw imagery.
     */
    public function store(StoreFaceEnrollmentRequest $request): ApiResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $version = (int) FaceTemplate::where('user_id', $user->id)->max('version') + 1;

        $template = FaceTemplate::create([
            'user_id' => $user->id,
            'version' => $version,
            'status' => FaceTemplate::STATUS_PENDING,
            'provider' => $validated['provider'] ?? null,
            'template_ref' => $validated['template_ref'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        $enrollment = FaceEnrollmentRequest::create([
            'user_id' => $user->id,
            'face_template_id' => $template->id,
            'status' => FaceEnrollmentRequest::STATUS_PENDING,
            'provider' => $validated['provider'] ?? null,
            'sample_count' => $validated['sample_count'],
            'metadata' => $validated['metadata'] ?? null,
            'submitted_at' => now(),
        ]);

        return ApiResponse::success([
            'enrollment' => $this->enrollmentPayload($enrollment),
            'template' => $this->templatePayload($template),
        ], 'Pendaftaran wajah diterima dan menunggu pemrosesan.', 201);
    }

    public function status(Request $request, int $id): ApiResponse
    {
        $enrollment = FaceEnrollmentRequest::where('user_id', $request->user()->id)->find($id);

        if (! $enrollment) {
            return ApiResponse::error('Permintaan enrollment tidak ditemukan.', 404);
        }

        return ApiResponse::success($this->enrollmentPayload($enrollment));
    }

    public function index(Request $request): ApiResponse
    {
        $templates = FaceTemplate::where('user_id', $request->user()->id)
            ->orderByDesc('version')
            ->get();

        return ApiResponse::success(
            $templates->map(fn (FaceTemplate $template) => $this->templatePayload($template)),
        );
    }

    public function revoke(Request $request, int $id): ApiResponse
    {
        $template = FaceTemplate::where('user_id', $request->user()->id)->find($id);

        if (! $template) {
            return ApiResponse::error('Template wajah tidak ditemukan.', 404);
        }

        if ($template->status === FaceTemplate::STATUS_DELETED) {
            return ApiResponse::error('Template wajah sudah dihapus.', 422);
        }

        $template->update([
            'status' => FaceTemplate::STATUS_REVOKED,
            'revoked_at' => now(),
        ]);

        return ApiResponse::success($this->templatePayload($template->fresh()), 'Template wajah dicabut.');
    }

    public function destroy(Request $request, int $id): ApiResponse
    {
        $template = FaceTemplate::where('user_id', $request->user()->id)->find($id);

        if (! $template) {
            return ApiResponse::error('Template wajah tidak ditemukan.', 404);
        }

        $template->update([
            'status' => FaceTemplate::STATUS_DELETED,
            'deleted_at' => now(),
        ]);

        return ApiResponse::success($this->templatePayload($template->fresh()), 'Template wajah dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function enrollmentPayload(FaceEnrollmentRequest $enrollment): array
    {
        return [
            'id' => $enrollment->id,
            'status' => $enrollment->status,
            'provider' => $enrollment->provider,
            'sample_count' => $enrollment->sample_count,
            'rejection_reason' => $enrollment->rejection_reason,
            'face_template_id' => $enrollment->face_template_id,
            'submitted_at' => $enrollment->submitted_at?->toIso8601String(),
            'processed_at' => $enrollment->processed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function templatePayload(FaceTemplate $template): array
    {
        return [
            'id' => $template->id,
            'version' => $template->version,
            'status' => $template->status,
            'provider' => $template->provider,
            'enrolled_at' => $template->enrolled_at?->toIso8601String(),
            'revoked_at' => $template->revoked_at?->toIso8601String(),
            'deleted_at' => $template->deleted_at?->toIso8601String(),
        ];
    }
}
