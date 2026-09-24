<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every public tag access and action (sections 13 & 43): view, vehicle_block,
 * lights_on, important_message, emergency, contact_owner. Kept lean and
 * append-only; aggregated into analytics_daily_stats for reporting so this
 * table is never queried directly for dashboard charts (section 43).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('action_type', [
                'view', 'vehicle_block', 'lights_on', 'important_message', 'emergency', 'contact_owner',
            ]);
            $table->json('payload')->nullable(); // sanitized plain-text message content only
            $table->enum('status', ['success', 'rate_limited', 'blocked', 'invalid'])->default('success');
            $table->string('request_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tag_id', 'created_at']);
            $table->index(['action_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
