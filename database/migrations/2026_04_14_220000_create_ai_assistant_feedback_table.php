<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_assistant_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 120);
            $table->string('message_id', 120)->nullable();
            $table->string('intent', 50)->nullable();
            $table->smallInteger('rating');
            $table->string('reason', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'created_at']);
            $table->index(['intent', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_assistant_feedback');
    }
};
