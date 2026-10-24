<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete()->unique();
            $table->string('employee_number', 32)->unique();
            $table->string('full_name', 255);
            $table->string('work_email', 255)->unique();
            $table->string('phone_e164', 20)->nullable()->unique();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->restrictOnDelete();
            $table->string('job_title', 255)->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('employment_start')->nullable();
            $table->string('employment_status', 32)->default('probation');
            $table->timestampTz('face_registered_at')->nullable();
            $table->timestampsTz();

            $table->index('division_id');
            $table->index('manager_id');
            $table->index('employment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
