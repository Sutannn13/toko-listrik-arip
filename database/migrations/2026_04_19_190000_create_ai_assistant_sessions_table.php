<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_assistant_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 120)->unique();
            $table->string('channel', 50)->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('last_page_path', 255)->nullable();
            $table->string('last_page_title', 180)->nullable();
            $table->string('last_intent', 50)->nullable();
            $table->string('last_message_id', 120)->nullable();
            $table->unsignedInteger('turns_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'last_activity_at']);
            $table->index(['channel', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_assistant_sessions');
    }
};
