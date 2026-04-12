<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'reviews_user_product_unique_idx';

    public function up(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        $duplicateGroups = DB::table('reviews')
            ->select('user_id', 'product_id', DB::raw('MAX(id) as keep_id'))
            ->groupBy('user_id', 'product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            DB::table('reviews')
                ->where('user_id', $group->user_id)
                ->where('product_id', $group->product_id)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }

        try {
            Schema::table('reviews', function (Blueprint $table) {
                $table->unique(['user_id', 'product_id'], self::INDEX_NAME);
            });
        } catch (\Throwable $e) {
            // Ignore if index already exists in environments with manual patching.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        try {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropUnique(self::INDEX_NAME);
            });
        } catch (\Throwable $e) {
            // Ignore if index is already missing.
        }
    }
};
