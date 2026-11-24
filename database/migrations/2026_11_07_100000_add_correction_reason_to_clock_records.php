<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clock_records', function (Blueprint $table) {
            $table->string('correction_reason', 500)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('clock_records', function (Blueprint $table) {
            $table->dropColumn('correction_reason');
        });
    }
};
