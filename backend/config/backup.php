<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Encryption Key
    |--------------------------------------------------------------------------
    |
    | Deliberately separate from APP_KEY (section 27: use secure key
    | management, support key rotation) — rotating the session/cookie key
    | should never silently invalidate historical backups, and vice versa.
    | Generate one with: openssl rand -base64 32
    |
    */

    'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Section 41/52: backups are pruned after this many days. Overridable
    | per-run with `--keep-days=`.
    |
    */

    'retention_days' => env('BACKUP_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Admin Credentials for Restore
    |--------------------------------------------------------------------------
    |
    | CREATE DATABASE / DROP DATABASE require a role with the CREATEDB
    | privilege. The app's own runtime role (DB_USERNAME) is deliberately
    | least-privilege and does NOT have this — see docs/DATABASE.md. Restore
    | tooling is an operations task, not a runtime app function, so it uses
    | its own separate, more-privileged credential rather than escalating
    | the app role (which would weaken it for its actual job).
    |
    */

    'admin_username' => env('DB_BACKUP_ADMIN_USERNAME', env('DB_USERNAME')),
    'admin_password' => env('DB_BACKUP_ADMIN_PASSWORD', env('DB_PASSWORD')),

];
