<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unified codes: one physical code that carries BOTH a QR image and an NFC
 * chip, both holding the same /t/<token> link. Adds the value `code` next to the
 * existing `qr` and `nfc` (older rows keep theirs and keep working).
 *
 * `type` / `tag_type` were created as enums, which PostgreSQL enforces with a
 * CHECK constraint; the constraint is replaced to allow the new value.
 */
return new class extends Migration
{
    private const CHECKS = [
        ['tags', 'type', 'tags_type_check'],
        ['tag_batches', 'tag_type', 'tag_batches_tag_type_check'],
    ];

    public function up(): void
    {
        $driver = DB::getDriverName();

        foreach (self::CHECKS as [$table, $column, $constraint]) {
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} CHECK ({$column} IN ('qr', 'nfc', 'code'))");
            } else {
                // SQLite (tests) and others: re-declare the column as a plain string.
                Schema::table($table, fn ($t) => $t->string($column)->change());
            }
        }
    }

    public function down(): void
    {
        // Unified codes cannot be represented by the old constraint; leaving it relaxed is harmless.
    }
};
