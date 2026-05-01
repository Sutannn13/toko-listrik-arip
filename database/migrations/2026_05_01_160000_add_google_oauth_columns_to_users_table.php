<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add OAuth columns for Google login support.
     *
     * - google_id: unique identifier from Google (sub claim)
     * - avatar: URL to Google profile photo
     * - provider: auth provider origin ('local' or 'google')
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('profile_photo_path');
            $table->string('avatar', 2048)->nullable()->after('google_id');
            $table->string('provider', 20)->default('local')->after('avatar');

            // Allow NULL password for Google-only users.
            // Existing local users already have hashed passwords — this is backward compatible.
            $table->string('password')->nullable()->change();

            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['provider']);
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'avatar', 'provider']);

            // Restore password to NOT NULL (set any NULL passwords to empty string first)
            $table->string('password')->nullable(false)->default('')->change();
        });
    }
};
