<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->unique()->constrained('tags')->cascadeOnDelete();
            $table->string('error_correction_level', 1)->default('H'); // required for print durability
            $table->string('image_svg_path')->nullable();
            $table->string('image_png_path')->nullable();
            $table->unsignedInteger('print_sheet_position')->nullable();
            $table->timestamps();
        });

        Schema::create('nfc_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->unique()->constrained('tags')->cascadeOnDelete();
            $table->string('chip_uid')->nullable()->unique(); // hardware UID, not personal data
            $table->boolean('rewritable')->default(false);
            $table->enum('provisioning_status', ['unprovisioned', 'provisioned', 'locked'])->default('unprovisioned');
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfc_tags');
        Schema::dropIfExists('qr_tags');
    }
};
