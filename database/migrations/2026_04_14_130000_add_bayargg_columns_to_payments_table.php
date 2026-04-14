<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway_provider', 50)->nullable()->after('method');
            $table->string('gateway_invoice_id')->nullable()->after('payment_code');
            $table->string('gateway_status', 30)->nullable()->after('status');
            $table->timestamp('gateway_expires_at')->nullable()->after('paid_at');
            $table->text('gateway_payment_url')->nullable()->after('proof_url');
            $table->string('gateway_paid_reference')->nullable()->after('gateway_status');
            $table->json('gateway_payload')->nullable()->after('notes');

            $table->index(['gateway_provider', 'gateway_invoice_id'], 'payments_gateway_provider_invoice_idx');
            $table->index('gateway_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_gateway_provider_invoice_idx');
            $table->dropIndex(['gateway_status']);

            $table->dropColumn([
                'gateway_provider',
                'gateway_invoice_id',
                'gateway_status',
                'gateway_expires_at',
                'gateway_payment_url',
                'gateway_paid_reference',
                'gateway_payload',
            ]);
        });
    }
};
