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
        $columnsToAdd = [];

        if (!Schema::hasColumn('warranty_claims', 'damage_proof_url')) {
            $columnsToAdd[] = 'damage_proof_url';
        }

        if (!Schema::hasColumn('warranty_claims', 'damage_proof_mime')) {
            $columnsToAdd[] = 'damage_proof_mime';
        }

        if ($columnsToAdd !== []) {
            Schema::table('warranty_claims', function (Blueprint $table) use ($columnsToAdd) {
                if (in_array('damage_proof_url', $columnsToAdd, true)) {
                    $table->string('damage_proof_url')->nullable()->after('admin_notes');
                }

                if (in_array('damage_proof_mime', $columnsToAdd, true)) {
                    $table->string('damage_proof_mime', 120)->nullable()->after('damage_proof_url');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columnsToDrop = [];

        if (Schema::hasColumn('warranty_claims', 'damage_proof_mime')) {
            $columnsToDrop[] = 'damage_proof_mime';
        }

        if (Schema::hasColumn('warranty_claims', 'damage_proof_url')) {
            $columnsToDrop[] = 'damage_proof_url';
        }

        if ($columnsToDrop !== []) {
            Schema::table('warranty_claims', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
