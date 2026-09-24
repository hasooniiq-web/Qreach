<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Security-event subsystem (section 22): AUTH_FAILURE, OTP_ABUSE, QR_GUESSING,
 * RATE_LIMIT_TRIGGERED, PRIVILEGE_CHANGE, MASS_EXPORT, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // e.g. AUTH_FAILURE, QR_GUESSING
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->enum('actor_type', ['customer', 'admin', 'unknown'])->default('unknown');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('request_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_type', 'created_at']);
            $table->index(['severity', 'resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
