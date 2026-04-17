<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'warranty_days')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedSmallInteger('warranty_days')
                    ->default(0)
                    ->after('is_electronic')
                    ->comment('Masa garansi per produk dalam hari. 0 = tanpa garansi, maksimal 365 hari.');
            });
        }

        DB::table('products')
            ->where('is_electronic', true)
            ->where('warranty_days', 0)
            ->update(['warranty_days' => 7]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'warranty_days')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('warranty_days');
            });
        }
    }
};
