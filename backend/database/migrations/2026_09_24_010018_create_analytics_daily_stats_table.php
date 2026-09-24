<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-aggregated daily metrics (section 43), populated by a scheduled job
 * reading from scans/audit_logs/etc. Dashboard charts read only from here,
 * never from the raw transactional tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('metric_key'); // e.g. scans_total, qr_activations, contact_requests
            $table->string('dimension')->default('all'); // e.g. tag_type=qr, action_type=emergency
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();

            $table->unique(['date', 'metric_key', 'dimension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_stats');
    }
};
