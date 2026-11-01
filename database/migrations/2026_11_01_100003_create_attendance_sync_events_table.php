<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Individual offline events. Each event carries its own idempotency key
     * and a client event id, so a re-sent batch cannot create duplicates.
     */
    public function up(): void
    {
        Schema::create('attendance_sync_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('attendance_sync_batches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('client_event_id', 100);
            $table->string('idempotency_key', 100)->unique();
            $table->string('event_type', 20);
            $table->timestampTz('occurred_at');
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->string('status', 20)->default('pending_sync');
            $table->string('error_message', 255)->nullable();
            $table->foreignId('clock_record_id')->nullable()->constrained('clock_records')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['user_id', 'client_event_id']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sync_events');
    }
};
