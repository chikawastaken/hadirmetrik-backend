<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores opaque face template references only. Raw images and embeddings
     * never live in this table; `template_ref` points at the provider's
     * versioned artefact. References: docs/roadmap.md §7 (Phase 4).
     */
    public function up(): void
    {
        Schema::create('face_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('pending');
            $table->string('provider', 50)->nullable();
            $table->string('template_ref', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('enrolled_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('deleted_at')->nullable();
            $table->timestampsTz();

            $table->unique(['user_id', 'version']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_templates');
    }
};
