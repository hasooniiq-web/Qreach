<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A contact request becomes a small two-way conversation between the visitor
 * (anonymous, identified only by the unguessable request uuid in their link)
 * and the owner. Conversations are deleted 15 days after their last activity.
 * Customers also get a profile photo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_request_id')->constrained()->cascadeOnDelete();
            $table->enum('sender', ['visitor', 'owner']);
            $table->string('body', 500);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['contact_request_id', 'id']);
        });

        Schema::table('contact_requests', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->index();
        });
        DB::table('contact_requests')->update(['last_activity_at' => DB::raw('created_at')]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('avatar_path'));
        Schema::table('contact_requests', fn (Blueprint $table) => $table->dropColumn('last_activity_at'));
        Schema::dropIfExists('contact_request_messages');
    }
};
