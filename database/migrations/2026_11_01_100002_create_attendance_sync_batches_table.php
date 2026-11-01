<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One batch per client upload. `idempotency_key` makes replays safe:
     * the same key always returns the original batch instead of creating
     * duplicate attendance.
     */
    public function up(): void
    {
        Schema::create('attendance_sync_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('idempotency_key', 100)->unique();
            $table->string('device_id', 100)->nullable();
            $table->string('status', 20)->default('received');
            $table->unsignedInteger('total_events')->default(0);
            $table->unsignedInteger('processed_events')->default(0);
            $table->unsignedInteger('failed_events')->default(0);
            $table->string('payload_hash', 64)->nullable();
            $table->timestampTz('received_at')->useCurrent();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sync_batches');
    }
};
