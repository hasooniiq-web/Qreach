<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer accounts. Authentication is phone + OTP only (see otp_codes table) —
 * no password column, per section 23 of the SmartTag spec. Internal `id` is
 * never exposed publicly; `uuid` is the public-facing identifier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('phone', 32)->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('name')->nullable();
            $table->enum('status', ['active', 'suspended', 'deleted'])->default('active');
            $table->string('locale', 8)->default('ar');
            $table->string('timezone', 64)->default('Asia/Riyadh');
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // Web/dashboard sessions (admin Blade auth uses the `database` session
        // driver). Column is named `user_id` because Laravel's
        // DatabaseSessionHandler hardcodes that column name regardless of
        // guard — it holds the authenticated admin's id here, not a customer.
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
