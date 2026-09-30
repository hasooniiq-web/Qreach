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
        Schema::table('users', function (Blueprint $table) {
            // A cover (banner) photo alongside the existing avatar. Both are
            // off by default on the public scan page, same as the name.
            $table->string('cover_path')->nullable()->after('avatar_path');
            $table->boolean('show_avatar_publicly')->default(false)->after('show_name_publicly');
            $table->boolean('show_cover_publicly')->default(false)->after('show_avatar_publicly');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cover_path', 'show_avatar_publicly', 'show_cover_publicly']);
        });
    }
};
