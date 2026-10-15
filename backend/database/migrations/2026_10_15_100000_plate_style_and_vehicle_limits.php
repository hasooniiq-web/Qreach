<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - vehicles.plate_style: how the plate is drawn — "english" (the current Iraqi plate with the
 *   governorate number), "german" (the older plate with Arabic digits and the governorate below)
 *   or "other" (the plain card the app has always drawn; what every existing vehicle keeps).
 * - Vehicles per plan: the free plan holds one vehicle, Plus three.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('plate_style', 10)->default('other');
        });

        DB::table('subscription_plans')->where('slug', 'basic')->update(['max_vehicles' => 1]);
        DB::table('subscription_plans')->where('slug', 'plus')->where('max_vehicles', 5)->update(['max_vehicles' => 3]);
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('plate_style');
        });

        DB::table('subscription_plans')->where('slug', 'plus')->where('max_vehicles', 3)->update(['max_vehicles' => 5]);
    }
};
