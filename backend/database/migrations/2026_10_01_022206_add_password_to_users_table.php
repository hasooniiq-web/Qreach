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
        Schema::table('users', function (Blueprint $table) {
            // Nullable: existing accounts were created phone+OTP only, with no
            // password at all. They set one the first time through the
            // password-reset OTP flow (also used for "forgot password" later);
            // until then they simply cannot use /auth/login.
            $table->string('password')->nullable()->after('phone_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
