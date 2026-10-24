<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * References: docs/architecture.md §6.4 (employee_invitations, invitation_deliveries), ADR 0007.
     */
    public function up(): void
    {
        Schema::create('employee_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('purpose', 32)->default('face_enrollment');
            $table->string('token_hash', 64)->unique();
            $table->string('status', 32)->default('pending');
            $table->json('requested_channels');
            $table->timestampTz('expires_at');
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('last_sent_at')->nullable();
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index('status');
            $table->index('expires_at');
            $table->index('employee_id');
        });

        Schema::create('invitation_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained('employee_invitations')->cascadeOnDelete();
            $table->string('channel', 16);
            $table->string('recipient', 255);
            $table->string('provider', 64)->nullable();
            $table->string('provider_message_id', 255)->nullable();
            $table->string('status', 32)->default('queued');
            $table->integer('attempt')->default(1);
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampsTz();

            $table->index(['invitation_id', 'channel', 'status']);
            $table->unique(['invitation_id', 'channel', 'attempt']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation_deliveries');
        Schema::dropIfExists('employee_invitations');
    }
};
