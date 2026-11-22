<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Leave, permission, and overtime requests with per-step approvals.
     * References: docs/architecture.md §5.3, §6.4.
     */
    public function up(): void
    {
        Schema::create('administrative_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('request_type', 16);
            $table->string('category', 24)->nullable();
            $table->string('special_reason', 24)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('day_portion', 16)->default('full');
            $table->decimal('day_count', 4, 1)->nullable();
            $table->date('overtime_date')->nullable();
            $table->decimal('overtime_hours', 4, 2)->nullable();
            $table->text('reason');
            $table->string('status', 16)->default('submitted');
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->index(['employee_id', 'request_type', 'start_date']);
            $table->index(['status', 'submitted_at']);
        });

        Schema::create('request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrative_request_id')
                ->constrained('administrative_requests')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('round')->default(1);
            $table->unsignedSmallInteger('step_order');
            $table->string('approver_role', 16);
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 16)->default('pending');
            $table->text('note')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->timestampsTz();

            $table->unique(['administrative_request_id', 'round', 'step_order'], 'request_approvals_round_step_unique');
            $table->index(['approver_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_approvals');
        Schema::dropIfExists('administrative_requests');
    }
};
