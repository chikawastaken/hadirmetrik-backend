<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Task management, per-assignment progress, submissions, evidence, and comments.
     * References: docs/architecture.md §6.4, ADR 0011, ADR 0014.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('priority', 8)->default('medium');
            $table->string('status', 16)->default('draft');
            $table->boolean('requires_photo_proof')->default(false);
            $table->json('checklist_template');
            $table->timestampTz('due_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'due_at']);
            $table->index('created_by');
            $table->index('division_id');
        });

        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('status', 24)->default('assigned');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['task_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
        });

        Schema::create('task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_assignment_id')->constrained('task_assignments')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->json('checklist');
            $table->string('review_status', 24)->default('pending');
            $table->unsignedTinyInteger('review_rating')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('submitted_at')->useCurrent();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();

            $table->index(['review_status', 'submitted_at']);
        });

        // One pending submission per assignment, enforced by the database.
        DB::statement(
            "CREATE UNIQUE INDEX task_submissions_pending_unique ON task_submissions (task_assignment_id) WHERE review_status = 'pending'"
        );

        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('task_submission_id')->nullable()->constrained('task_submissions')->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('storage_disk', 32)->default('local');
            $table->string('object_key', 512)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index('task_id');
            $table->index('task_submission_id');
        });

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestampsTz();

            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_submissions');
        Schema::dropIfExists('task_assignments');
        Schema::dropIfExists('tasks');
    }
};
