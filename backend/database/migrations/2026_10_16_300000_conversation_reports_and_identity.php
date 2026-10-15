<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - conversation_reports: either side of a conversation can report the other (a harassing
 *   visitor or account, spam, abuse). They all land in the dashboard.
 * - contact_requests.reporter_identity_visible: the person who started a conversation from the
 *   app chooses whether the owner sees their name; hidden by default. The owner then only sees
 *   a trust badge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by_user_id')->constrained('users')->cascadeOnDelete();
            // Which side filed it: the vehicle's owner, or the customer who scanned the code.
            $table->string('reported_by_role', 10);
            $table->foreignId('reported_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 20); // harassment | spam | abuse | scam | other
            $table->string('details', 300)->nullable();
            $table->string('status', 10)->default('new'); // new | reviewed
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::table('contact_requests', function (Blueprint $table) {
            $table->boolean('reporter_identity_visible')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->dropColumn('reporter_identity_visible');
        });
        Schema::dropIfExists('conversation_reports');
    }
};
