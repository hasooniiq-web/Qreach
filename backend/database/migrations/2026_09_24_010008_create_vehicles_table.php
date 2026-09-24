<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // public identifier
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nickname')->nullable();
            $table->string('plate_number', 32);
            $table->string('plate_country', 2)->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color')->nullable();
            $table->enum('vehicle_type', ['sedan', 'suv', 'truck', 'motorcycle', 'van', 'other'])->default('sedan');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            // Which vehicle fields are shown on the public tag page, e.g. {"make": true, "plate_number": false}.
            $table->json('privacy_settings')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
