<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full history of assignment/reassignment/revocation (section 11), independent
 * of `tags.vehicle_id` which only holds the current assignment. Every row here
 * has a corresponding audit_logs entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // owner at the time
            $table->enum('event', ['assigned', 'reassigned', 'revoked', 'replaced'])->default('assigned');
            $table->enum('performed_by_type', ['customer', 'admin', 'system'])->default('customer');
            $table->unsignedBigInteger('performed_by_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('unassigned_at')->nullable();
            $table->timestamps();

            $table->index(['tag_id', 'assigned_at']);
            $table->index(['vehicle_id', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_assignments');
    }
};
