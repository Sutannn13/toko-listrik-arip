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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade'); // Relasi ke tabel kategori
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('price');
            $table->integer('stock')->default(0);
            $table->enum('unit', ['pcs', 'meter', 'roll', 'box'])->default('pcs'); // Wajib ada buat toko listrik
            $table->json('specifications')->nullable(); // Simpan data kayak {"watt": "10W", "voltase": "220V"}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
