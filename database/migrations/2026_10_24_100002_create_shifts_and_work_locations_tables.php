<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * References: docs/architecture.md §6.4 (shifts, work_locations).
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->boolean('is_overnight')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('work_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->text('address')->nullable();
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->integer('geofence_radius_m')->default(100);
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_locations');
        Schema::dropIfExists('shifts');
    }
};
