# Disaster Recovery

## Current status: implemented and actually tested (not just written)

## What exists

1. **`php artisan backup:database`** — runs `pg_dump -Fc` (Postgres custom format, compressed, selectively restorable), then encrypts the dump with AES-256-CBC via a dedicated `BACKUP_ENCRYPTION_KEY` (deliberately separate from `APP_KEY` — see the command's docblock for why). Refuses to run at all if the key isn't set, rather than ever writing an unencrypted dump. Prunes backups older than `BACKUP_RETENTION_DAYS` (default 30). Scheduled daily at 02:00 (`routes/console.php`).
2. **`php artisan backup:restore {file}`** — decrypts and restores via `pg_restore`. Defaults to a throwaway `*_restore_test` database and will only ever target the real production database if you explicitly pass `--target=<production db name>` *and* confirm an interactive prompt — restoring over live data by accident is not possible with the default invocation.

## The restore was actually verified, end to end

This is the one place in the project where "tested" means a human-verifiable, reproducible result rather than a passing assertion:

1. Ran `php artisan backup:database` against the real dev database.
2. Ran `php artisan backup:restore <the resulting file> --drop-target`, which decrypted the backup and restored it into a fresh `smarttag_restore_test` database.
3. Compared row counts for `users`, `tags`, `admins`, `roles`, and `analytics_daily_stats` between the source and restored databases — **identical** for every table.
4. Compared actual content (the seeded admin's email address) between source and restored — **identical**.
5. Dropped the scratch database afterward.

This matches the spec's own principle directly: "a backup that has never been restored/tested must not be considered reliable" — this one has been, once, manually. It is not yet a recurring, automated drill (see gaps below).

## A real bug found and fixed while building this

The restore command's `DROP DATABASE`/`CREATE DATABASE` steps initially used the app's own least-privilege runtime database role (`smarttag_app`), which — correctly, by design — does **not** have the `CREATEDB` privilege (see `docs/DATABASE.md`). The command silently continued past the failed `psql` calls (no exit-code check) and only failed later with a confusing "database does not exist" error from `pg_restore`. Fixed by: (a) giving the restore tooling its own separate admin credential (`DB_BACKUP_ADMIN_USERNAME`/`PASSWORD`) rather than escalating the app's runtime role, and (b) actually checking every admin `psql` call's exit code and aborting immediately on failure instead of proceeding silently.

## What's still missing

- **Off-site/object storage copy** — backups currently land on local disk (`storage/app/private/backups/`, gitignored). Production needs these shipped to S3/equivalent immediately after creation, not left on the same machine as the database they're backing up.
- **Recurring restore drills** — the verification above was a one-time manual run, not a scheduled/automated quarterly drill.
- **QR/NFC image files**: not backed up separately, but this is fine — they're deterministically re-renderable from `tags.public_token` data already in the database backup (the image just encodes `{app_url}/t/{token}`), so no separate backup mechanism is needed for them.
- **Runbook** for database-unreachable / bad-migration / stuck-queue scenarios — not written yet.

## Recommendation before production launch

Off-site backup shipping (the first gap above) is the one item that should not be deferred — a backup sitting on the same disk/machine as the production database doesn't protect against the disk or machine failing.
