<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Section 41: automated, encrypted backups with a retention policy.
 *
 * Uses `pg_dump -Fc` (Postgres custom format — compressed, and restorable
 * selectively/in parallel via pg_restore) piped through OpenSSL AES-256-CBC
 * encryption, so the file at rest is never plaintext even though it
 * contains customer PII (phone numbers, emergency contacts, etc.).
 *
 * The encryption key is a dedicated `BACKUP_ENCRYPTION_KEY` — deliberately
 * NOT the same as `APP_KEY`, so rotating one never silently breaks the
 * other, and so a leaked APP_KEY (used for session/cookie encryption)
 * doesn't also expose every historical backup.
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep-days= : Override the retention period in days}';

    protected $description = 'Create an encrypted PostgreSQL backup and prune backups past the retention window';

    public function handle(): int
    {
        $key = config('backup.encryption_key');

        if (! $key) {
            $this->error('BACKUP_ENCRYPTION_KEY is not set — refusing to write an unencrypted backup.');

            return self::FAILURE;
        }

        $timestamp = now()->format('Y-m-d_His');
        $encryptedRelativePath = "backups/smarttag_{$timestamp}.dump.enc";

        Storage::disk('local')->makeDirectory('backups');

        // Resolve via Storage::path() rather than storage_path('app/...')
        // directly — Laravel's 'local' disk root is storage/app/private,
        // not storage/app, and hardcoding the latter silently writes to a
        // directory Storage::disk('local') never actually manages.
        $dumpPath = Storage::disk('local')->path("tmp_{$timestamp}.dump");
        $encryptedPath = Storage::disk('local')->path($encryptedRelativePath);

        $this->info('Running pg_dump...');

        $dbConfig = config('database.connections.pgsql');

        $dump = new Process([
            'pg_dump',
            '-Fc',
            '-h', $dbConfig['host'],
            '-p', (string) $dbConfig['port'],
            '-U', $dbConfig['username'],
            '-d', $dbConfig['database'],
            '-f', $dumpPath,
        ]);
        $dump->setEnv(['PGPASSWORD' => $dbConfig['password']]);
        $dump->setTimeout(600);
        $dump->run();

        if (! $dump->isSuccessful()) {
            $this->error('pg_dump failed: '.$dump->getErrorOutput());

            return self::FAILURE;
        }

        $this->info('Encrypting dump (AES-256-CBC)...');

        $encrypt = Process::fromShellCommandline(
            'openssl enc -aes-256-cbc -pbkdf2 -salt -in '.escapeshellarg($dumpPath).
            ' -out '.escapeshellarg($encryptedPath).' -pass pass:'.escapeshellarg($key)
        );
        $encrypt->run();

        @unlink($dumpPath);

        if (! $encrypt->isSuccessful()) {
            $this->error('Encryption failed: '.$encrypt->getErrorOutput());

            return self::FAILURE;
        }

        $sizeMb = round(filesize($encryptedPath) / 1_048_576, 2);
        $this->info("Backup written: storage/app/{$encryptedRelativePath} ({$sizeMb} MB)");

        $this->pruneOldBackups((int) ($this->option('keep-days') ?? config('backup.retention_days')));

        return self::SUCCESS;
    }

    private function pruneOldBackups(int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays);
        $deleted = 0;

        foreach (Storage::disk('local')->files('backups') as $file) {
            $modifiedAt = Storage::disk('local')->lastModified($file);

            if ($modifiedAt < $cutoff->timestamp) {
                Storage::disk('local')->delete($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Pruned {$deleted} backup(s) older than {$keepDays} days.");
        }
    }
}
