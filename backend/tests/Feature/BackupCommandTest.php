<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Full backup/restore round-trip requires a real PostgreSQL connection and
 * the pg_dump/pg_restore/openssl binaries — not something the SQLite-based
 * CI suite can exercise (see phpunit.xml). That round-trip was instead
 * verified manually: a real backup was taken, decrypted, and restored into
 * a scratch database, and every table's row count and admin email matched
 * the source exactly (see docs/DISASTER_RECOVERY.md). This test only locks
 * in the one safety guard that doesn't need a database at all.
 */
class BackupCommandTest extends TestCase
{
    public function test_backup_refuses_to_run_without_an_encryption_key(): void
    {
        Config::set('backup.encryption_key', null);

        $this->artisan('backup:database')
            ->expectsOutputToContain('refusing to write an unencrypted backup')
            ->assertFailed();
    }
}
