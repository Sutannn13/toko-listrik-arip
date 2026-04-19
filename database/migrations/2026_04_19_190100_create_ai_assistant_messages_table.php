<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_assistant_session_id')->constrained('ai_assistant_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 20);
            $table->string('intent', 50)->nullable();
            $table->string('message_id', 120)->nullable();
            $table->text('content');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['ai_assistant_session_id', 'created_at']);
            $table->index(['role', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_assistant_messages');
    }
};
