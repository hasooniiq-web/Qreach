<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A code answers to two channels: the printed QR and the NFC chip. The owner can switch
 * either one off (keep only NFC, keep only QR, or both off = the code does not answer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->boolean('qr_enabled')->default(true);
            $table->boolean('nfc_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn(['qr_enabled', 'nfc_enabled']);
        });
    }
};
