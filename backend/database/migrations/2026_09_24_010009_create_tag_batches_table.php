<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('batch_code')->unique(); // e.g. ST-2026-000123
            $table->enum('tag_type', ['qr', 'nfc']);
            $table->unsignedInteger('quantity');
            $table->enum('status', ['pending', 'generated', 'exported', 'printed', 'completed'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_batches');
    }
};
