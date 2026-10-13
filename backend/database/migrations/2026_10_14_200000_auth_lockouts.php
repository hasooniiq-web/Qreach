<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Escalating sign-in lockouts. One row per (scope, identifier): the phone number of a
 * customer, the email of a dashboard admin. Kept in the database (not the cache) so a
 * lock survives a restart or a cache flush.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_lockouts', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20);          // customer | customer_otp | admin | admin_totp
            $table->string('identifier', 190);
            $table->unsignedSmallInteger('failures')->default(0);   // wrong tries in the current streak
            $table->unsignedSmallInteger('level')->default(0);      // how many lockouts so far (picks the duration)
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamps();

            $table->unique(['scope', 'identifier']);
            $table->index('locked_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_lockouts');
    }
};
