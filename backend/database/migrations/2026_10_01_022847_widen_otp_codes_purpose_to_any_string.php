<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * `purpose` was a fixed enum('register', 'login') — too narrow now that
     * password_reset exists too, and doctrine/dbal (needed to ->change() an
     * enum's allowed values) isn't installed. Dropping and re-adding as a
     * plain string sidesteps that and means a future purpose never needs a
     * migration again. Existing rows' purpose is lost, which is fine: OTP
     * codes expire within minutes (never audit-critical data).
     */
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropIndex(['phone', 'purpose', 'consumed_at']);
            $table->dropColumn('purpose');
        });

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('purpose')->default('login')->after('code_hash');
            $table->index(['phone', 'purpose', 'consumed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropIndex(['phone', 'purpose', 'consumed_at']);
            $table->dropColumn('purpose');
        });

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->enum('purpose', ['register', 'login'])->default('login')->after('code_hash');
            $table->index(['phone', 'purpose', 'consumed_at']);
        });
    }
};
