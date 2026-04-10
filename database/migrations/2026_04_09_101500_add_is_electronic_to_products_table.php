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
        if (!Schema::hasColumn('products', 'is_electronic')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_electronic')
                    ->default(false)
                    ->after('is_active')
                    ->comment('Produk elektronik berhak klaim garansi maksimal 7 hari');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'is_electronic')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('is_electronic');
            });
        }
    }
};
