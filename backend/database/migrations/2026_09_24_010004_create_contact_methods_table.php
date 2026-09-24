<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extensible social/contact method model (section 8) so future providers can be
 * added without a schema change to `users`. Each method carries its own privacy
 * state, enforced server-side wherever public data is rendered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['phone', 'email', 'instagram', 'facebook', 'website', 'other']);
            $table->string('value'); // raw handle/number/url; validated per type
            $table->string('label')->nullable();
            $table->enum('privacy_state', ['private', 'public', 'contact_request_only'])->default('private');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_methods');
    }
};
