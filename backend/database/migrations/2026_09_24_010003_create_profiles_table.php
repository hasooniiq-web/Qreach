<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extended, non-auth-critical profile data kept separate from `users` per the
 * privacy-by-design separation in section 28 (public vs private vs sensitive data).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('avatar_path')->nullable();
            $table->text('bio')->nullable();
            $table->json('notification_preferences')->nullable();
            // Default visibility for contact methods that don't set their own privacy_state.
            $table->json('privacy_settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
