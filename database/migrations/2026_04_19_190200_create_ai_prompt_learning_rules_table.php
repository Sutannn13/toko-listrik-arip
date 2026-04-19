<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompt_learning_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_key', 120)->unique();
            $table->string('intent', 50)->nullable();
            $table->string('source', 50)->default('negative_feedback');
            $table->json('trigger_keywords')->nullable();
            $table->text('directive');
            $table->unsignedInteger('negative_feedback_count')->default(0);
            $table->unsignedInteger('sample_count')->default(0);
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->unsignedSmallInteger('lookback_days')->default(30);
            $table->timestamp('last_learned_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metrics')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'confidence_score']);
            $table->index(['intent', 'negative_feedback_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_learning_rules');
    }
};
