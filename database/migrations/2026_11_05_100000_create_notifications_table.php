<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category', 16);
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestampTz('read_at')->nullable();
            $table->string('push_status', 16)->default('pending');
            $table->timestampTz('push_sent_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'read_at', 'created_at DESC']);
            $table->index(['user_id', 'push_status'])->where('push_status', 'pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
