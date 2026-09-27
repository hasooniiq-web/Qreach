<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QR/NFC lifecycle rebuild (spec: 6-stage lifecycle — Created, Sent to
 * Printing, Unassigned, Assigned, Replacement, Cancelled):
 *
 *   generated (Created) -> printing (Sent to Printing) -> unassigned
 *   -> assigned -> replaced (old code) | revoked (Cancelled)
 *
 * `revoked` already existed and was already terminal/irreversible
 * (App\Services\TagLifecycleService never allows re-activating it) — it
 * *is* "Cancelled"; there is no separate status to avoid duplicating that
 * business logic. `printing` and `unassigned` are new: printing tracked
 * "sent to the print shop" and "generated" but not yet available.
 *
 * `tag_lifecycle_events` is a new, append-only ledger of every status
 * transition a tag goes through — independent of `tag_assignments` (which
 * only records assignment-related events tied to a vehicle/user). This is
 * what makes "created", "sent to printing" and "unassigned" auditable,
 * which `tag_assignments` alone could not express.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The enum was a Postgres CHECK constraint, not a native type — widen it
        // to a plain string so new lifecycle values need no further migration.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('alter table tags drop constraint if exists tags_status_check');
        }
        Schema::table('tags', fn (Blueprint $t) => $t->string('status', 20)->default('generated')->change());

        Schema::create('tag_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->string('previous_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->text('reason')->nullable();
            $table->enum('performed_by_type', ['customer', 'admin', 'system'])->default('system');
            $table->unsignedBigInteger('performed_by_id')->nullable();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tag_id', 'created_at']);
            $table->index('new_status');
            $table->index(['vehicle_id']);
            $table->index(['user_id']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            // Optional; shown on the vehicle/user 360 view when the owner has it.
            $table->string('vin', 32)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', fn (Blueprint $t) => $t->dropColumn('vin'));
        Schema::dropIfExists('tag_lifecycle_events');
    }
};
