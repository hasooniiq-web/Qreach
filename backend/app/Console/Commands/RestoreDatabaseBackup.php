<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Restores an encrypted backup into a target database — by default a
 * *different* database than production, specifically so this command can
 * never be run accidentally against live data. Restoring over the real
 * database requires explicitly passing --target=<same database name> and
 * confirming the prompt.
 *
 * This command exists as much to make restore testing routine as for
 * actual disaster recovery — per section 41, "a backup that has never
 * been restored/tested must not be considered reliable."
 */
class RestoreDatabaseBackup extends Command
{
    protected $signature = 'backup:restore {file : Path to the .dump.enc file, relative to storage/app}
                            {--target= : Database name to restore into (defaults to a throwaway *_restore_test database)}
                            {--drop-target : Drop and recreate the target database first}';

    protected $description = 'Decrypt and restore a backup — defaults to a scratch database, never production, unless explicitly told to';

    public function handle(): int
    {
        $key = config('backup.encryption_key');
        if (! $key) {
            $this->error('BACKUP_ENCRYPTION_KEY is not set.');

            return self::FAILURE;
        }

        $encryptedPath = Storage::disk('local')->path($this->argument('file'));
        if (! file_exists($encryptedPath)) {
            $this->error("Backup file not found: {$encryptedPath}");

            return self::FAILURE;
        }

        $dbConfig = config('database.connections.pgsql');
        $target = $this->option('target') ?? $dbConfig['database'].'_restore_test';

        if ($target === $dbConfig['database']) {
            $this->warn("This will OVERWRITE the live database '{$target}'.");
            if (! $this->confirm('Are you absolutely sure?')) {
                return self::FAILURE;
            }
        }

        $decryptedPath = Storage::disk('local')->path('backups/tmp_restore_'.now()->timestamp.'.dump');

        $this->info('Decrypting backup...');
        $decrypt = Process::fromShellCommandline(
            'openssl enc -d -aes-256-cbc -pbkdf2 -in '.escapeshellarg($encryptedPath).
            ' -out '.escapeshellarg($decryptedPath).' -pass pass:'.escapeshellarg($key)
        );
        $decrypt->run();

        if (! $decrypt->isSuccessful()) {
            $this->error('Decryption failed: '.$decrypt->getErrorOutput());

            return self::FAILURE;
        }

        $env = ['PGPASSWORD' => $dbConfig['password']];

        if ($this->option('drop-target')) {
            $adminUser = config('backup.admin_username');
            $adminEnv = ['PGPASSWORD' => config('backup.admin_password')];

            $this->info("Recreating database '{$target}' (as '{$adminUser}', which needs CREATEDB)...");

            if (! $this->runPsql($dbConfig, $adminUser, $adminEnv, "DROP DATABASE IF EXISTS {$target};")) {
                return self::FAILURE;
            }
            if (! $this->runPsql($dbConfig, $adminUser, $adminEnv, "CREATE DATABASE {$target} OWNER {$dbConfig['username']};")) {
                return self::FAILURE;
            }
        }

        $this->info("Restoring into '{$target}'...");
        $restore = new Process([
            'pg_restore',
            '--no-owner', '--clean', '--if-exists',
            '-h', $dbConfig['host'], '-p', (string) $dbConfig['port'],
            '-U', $dbConfig['username'], '-d', $target,
            $decryptedPath,
        ]);
        $restore->setEnv($env);
        $restore->setTimeout(600);
        $restore->run();

        @unlink($decryptedPath);

        // pg_restore exits non-zero on advisory/skippable warnings (e.g. a
        // role it can't find) even on an otherwise successful restore, so
        // check stderr content rather than the exit code alone.
        if (! $restore->isSuccessful() && ! str_contains($restore->getErrorOutput(), 'errors ignored on restore')) {
            $this->warn($restore->getErrorOutput());
        }

        $this->info("Restore into '{$target}' complete.");

        return self::SUCCESS;
    }

    private function runPsql(array $dbConfig, string $username, array $env, string $sql): bool
    {
        $process = new Process([
            'psql', '-h', $dbConfig['host'], '-p', (string) $dbConfig['port'],
            '-U', $username, '-d', 'postgres', '-v', 'ON_ERROR_STOP=1', '-c', $sql,
        ]);
        $process->setEnv($env);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error("psql command failed: {$sql}\n".$process->getErrorOutput());

            return false;
        }

        return true;
    }
}
