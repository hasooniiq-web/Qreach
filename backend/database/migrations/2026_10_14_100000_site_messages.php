<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messages sent from the "Contact us" form on the public site. They are kept here
 * (and listed in the dashboard) so none is lost, whether or not an email could be sent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('message');
            $table->string('locale', 5)->default('ar');
            $table->string('status', 10)->default('new'); // new | read
            $table->string('ip', 45)->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_messages');
    }
};
