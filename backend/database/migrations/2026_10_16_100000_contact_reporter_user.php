<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Another customer can scan a vehicle's code from inside the app and carry on the
 * conversation there. Such a conversation remembers which account started it; the
 * owner is never shown who that is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->foreignId('reporter_user_id')->nullable()->after('vehicle_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reporter_user_id');
        });
    }
};
