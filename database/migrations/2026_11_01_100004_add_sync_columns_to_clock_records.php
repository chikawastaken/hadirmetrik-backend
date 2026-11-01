<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes online and offline clock records share the same provenance
     * fields. `idempotency_key` is unique so a retried sync cannot insert
     * a second record for the same client event.
     */
    public function up(): void
    {
        Schema::table('clock_records', function (Blueprint $table) {
            $table->string('source', 20)->default('online')->after('status');
            $table->string('device_id', 100)->nullable()->after('source');
            $table->string('idempotency_key', 100)->nullable()->unique()->after('device_id');
            $table->timestampTz('synced_at')->nullable()->after('idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clock_records', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['source', 'device_id', 'idempotency_key', 'synced_at']);
        });
    }
};
