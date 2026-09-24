<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generic tag abstraction (section 12) shared by QR and NFC so a vehicle can
 * carry both technologies as independently revocable identities. Type-specific
 * data lives in qr_tags/nfc_tags (1:1 child tables). `public_token` is the only
 * identifier ever exposed to the public scan page — never `id` or `uuid`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // internal reference id
            // 256-bit random token, base64url-encoded (~43 chars). High entropy,
            // unpredictable, unrelated to user/vehicle/serial — see docs/QR_SECURITY.md.
            $table->string('public_token', 64)->unique();
            $table->string('serial_number')->unique(); // human-readable, printed on the physical tag
            $table->enum('type', ['qr', 'nfc']);
            $table->foreignId('batch_id')->nullable()->constrained('tag_batches')->nullOnDelete();
            $table->enum('status', [
                'generated', 'available', 'activated', 'assigned', 'revoked', 'replaced', 'disabled',
            ])->default('generated');
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->foreignId('replaced_by_tag_id')->nullable()->constrained('tags')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index(['vehicle_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
