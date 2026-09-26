<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** vehicle_type stops being a closed DB enum so pickup and bus (and later types) need no schema change. */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('alter table vehicles drop constraint if exists vehicles_vehicle_type_check');
        }

        Schema::table('vehicles', fn (Blueprint $t) => $t->string('vehicle_type', 20)->default('sedan')->change());
    }

    public function down(): void
    {
        // Left as a string column: narrowing back to an enum could reject existing rows.
    }
};
