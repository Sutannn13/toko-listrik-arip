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
        // Kolom shipping_cost sudah dibuat di migrasi commerce utama, jadi tidak ditambahkan lagi.
        if (!Schema::hasColumn('products', 'weight')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('weight')->default(100)->after('stock')->comment('Berat dalam gram');
            });
        }

        $orderColumnsToAdd = [];
        if (!Schema::hasColumn('orders', 'courier')) {
            $orderColumnsToAdd[] = 'courier';
        }
        if (!Schema::hasColumn('orders', 'snap_token')) {
            $orderColumnsToAdd[] = 'snap_token';
        }

        if ($orderColumnsToAdd !== []) {
            Schema::table('orders', function (Blueprint $table) use ($orderColumnsToAdd) {
                if (in_array('courier', $orderColumnsToAdd, true)) {
                    $table->string('courier')->nullable()->after('shipping_cost');
                }

                if (in_array('snap_token', $orderColumnsToAdd, true)) {
                    $table->string('snap_token')->nullable()->after('status')->comment('Midtrans Snap Token');
                }
            });
        }

        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->tinyInteger('rating')->comment('1-5 stars');
                $table->text('comment')->nullable();
                $table->string('image')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');

        $orderColumnsToDrop = [];
        if (Schema::hasColumn('orders', 'courier')) {
            $orderColumnsToDrop[] = 'courier';
        }
        if (Schema::hasColumn('orders', 'snap_token')) {
            $orderColumnsToDrop[] = 'snap_token';
        }

        if ($orderColumnsToDrop !== []) {
            Schema::table('orders', function (Blueprint $table) use ($orderColumnsToDrop) {
                $table->dropColumn($orderColumnsToDrop);
            });
        }

        if (Schema::hasColumn('products', 'weight')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('weight');
            });
        }
    }
};
