<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TEMPORARY development aid: the last OTP sent to each phone, in plain text, so
 * a developer can log in without digging through laravel.log. It is only filled
 * when DEV_OTP_INBOX=true and the environment is not production, and rows older
 * than a day are pruned. Remove this table with the SMS provider integration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dev_otp_inbox', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 32)->unique();
            $table->string('code', 12);
            $table->unsignedInteger('sent_count')->default(1);
            $table->timestamp('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dev_otp_inbox');
    }
};
