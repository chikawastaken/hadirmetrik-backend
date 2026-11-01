<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks the lifecycle of an enrollment attempt. Until a face model is
     * available, requests stay `pending`; a later worker/provider marks them
     * `processing`, `completed`, `rejected`, or `failed`.
     */
    public function up(): void
    {
        Schema::create('face_enrollment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('face_template_id')->nullable()->constrained('face_templates')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('provider', 50)->nullable();
            $table->unsignedInteger('sample_count')->default(0);
            $table->string('rejection_reason', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('submitted_at')->useCurrent();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_enrollment_requests');
    }
};
