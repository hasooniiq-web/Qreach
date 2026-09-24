<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin (dashboard) accounts. Fully separate from customer `users` per section 20 —
 * admins never share a table, auth flow, or session guard with customers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password'); // Argon2id via config/hashing.php
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->text('totp_secret')->nullable(); // encrypted via cast
            $table->boolean('totp_enabled')->default(false);
            $table->json('recovery_codes')->nullable(); // encrypted via cast, hashed individual codes
            $table->boolean('must_change_password')->default(true);
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('admin_password_resets', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token'); // hashed
            $table->timestamp('created_at')->nullable();
            $table->timestamp('expires_at')->nullable();
        });

        // No FK from sessions.user_id to admins: that column is Laravel's
        // generic session-owner id (see migration 0001), not modeled as a
        // relation here.
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_password_resets');
        Schema::dropIfExists('admins');
    }
};
