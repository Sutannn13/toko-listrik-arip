<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_assistant_feedback', function (Blueprint $table) {
            $table->string('intent_detected', 50)->nullable()->after('intent');
            $table->string('intent_resolved', 50)->nullable()->after('intent_detected');
            $table->string('reason_code', 64)->nullable()->after('reason');
            $table->text('reason_detail')->nullable()->after('reason_code');
            $table->string('provider', 50)->nullable()->after('metadata');
            $table->string('model', 120)->nullable()->after('provider');
            $table->string('llm_status', 40)->nullable()->after('model');
            $table->boolean('fallback_used')->nullable()->after('llm_status');
            $table->unsignedInteger('response_latency_ms')->nullable()->after('fallback_used');
            $table->string('prompt_version', 64)->nullable()->after('response_latency_ms');
            $table->string('rule_version', 64)->nullable()->after('prompt_version');
            $table->string('response_source', 30)->nullable()->after('rule_version');
            $table->unsignedTinyInteger('feedback_version')->default(2)->after('response_source');

            $table->index(['intent_resolved', 'rating'], 'ai_feedback_intent_resolved_rating_idx');
            $table->index(['provider', 'llm_status'], 'ai_feedback_provider_status_idx');
            $table->index(['reason_code', 'created_at'], 'ai_feedback_reason_code_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ai_assistant_feedback', function (Blueprint $table) {
            $table->dropIndex('ai_feedback_intent_resolved_rating_idx');
            $table->dropIndex('ai_feedback_provider_status_idx');
            $table->dropIndex('ai_feedback_reason_code_created_idx');

            $table->dropColumn([
                'intent_detected',
                'intent_resolved',
                'reason_code',
                'reason_detail',
                'provider',
                'model',
                'llm_status',
                'fallback_used',
                'response_latency_ms',
                'prompt_version',
                'rule_version',
                'response_source',
                'feedback_version',
            ]);
        });
    }
};
