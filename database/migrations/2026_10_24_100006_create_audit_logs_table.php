<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * References: docs/architecture.md §6.4 (audit_logs), ADR 0008.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type', 100);
            $table->string('entity_id', 64)->nullable();
            $table->string('action', 100);
            $table->json('before_snapshot')->nullable();
            $table->json('after_snapshot')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('recorded_at')->useCurrent();
            $table->timestampsTz();

            $table->index(['entity_type', 'entity_id']);
            $table->index('actor_user_id');
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
