<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contact_request_messages', function (Blueprint $table) {
            // A message may carry one photo instead of (or alongside) text —
            // e.g. a visitor reporting "your car is blocking the road" with a
            // picture of it. Stored on the private `local` disk, same as
            // avatars, and served through an ownership-checked route rather
            // than a public URL.
            $table->string('image_path')->nullable()->after('body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_request_messages', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
