# Deployment

## Status: not yet deployed anywhere

This document describes what production deployment *should* look like based on the application's actual requirements — it has not been executed. No staging/production environment or hosting has been set up. CI (test/build verification on every push) exists — see the CI/CD section below — but that is not the same as a deployment pipeline; there is no automated deploy-to-server step.

## Environment configuration checklist

- `APP_ENV=production`, `APP_DEBUG=false` (never expose stack traces — the app's exception handlers already return structured JSON in production-safe form, but `APP_DEBUG=true` would still leak detail on non-JSON routes)
- `APP_URL` set to the real HTTPS domain (QR codes are generated using `config('app.url')` — changing this after tags are printed changes nothing retroactively since the token, not the domain, is what's validated, but new batches will encode whatever domain is configured at generation time)
- Real Postgres credentials, least-privilege role (already the local dev pattern — `smarttag_app`, not the superuser)
- `SESSION_SECURE_COOKIE` will default to `true` automatically outside `local` env (see `config/session.php`)
- A real `SmsGatewayContract` implementation bound in place of `LogSmsGateway` (`LogSmsGateway` actively refuses to run when `APP_ENV=production` — deploying without replacing it means OTP delivery hard-fails, which is intentional: failing loudly beats silently not sending codes)
- FCM/APNs credentials for actual push delivery (currently notifications only reach the database, not a device)
- `CORS_ALLOWED_ORIGINS` set explicitly if any browser-based client needs cross-origin API access (defaults to empty/deny-all)

## Required running processes

1. **Web server** (php-fpm + nginx/Apache, or equivalent) serving `public/index.php`
2. **Queue worker** (`php artisan queue:work --daemon`, supervised e.g. by Supervisor or systemd) — required for owner notifications to actually deliver; without it, jobs sit in the `jobs` table indefinitely
3. **Scheduler** (`php artisan schedule:run` via cron) — required now that `analytics:aggregate` runs daily (see `docs/ANALYTICS.md`); without it, the analytics dashboard silently stops accumulating new days

## Build steps

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Backups

**Implemented and tested** — see `docs/DISASTER_RECOVERY.md` for the full detail. `php artisan backup:run` produces an AES-256-CBC-encrypted `pg_dump`, and `php artisan backup:restore` restores it using a separate least-privilege backup/restore credential (kept apart from the app's runtime DB role, which deliberately lacks `CREATEDB`). This was verified with a real backup → restore → row-count-and-content comparison → cleanup cycle, not just written and assumed to work. Still missing: an actual production backup *schedule* (the command exists; nothing currently calls it on a cron) and a documented retention/rotation policy for how many backups to keep.

## CI/CD

**Implemented.** `.github/workflows/backend-ci.yml` runs `php artisan test` against SQLite plus a separate job that runs real migrations against a Postgres service container, and `vendor/bin/pint --test`. `.github/workflows/mobile-ci.yml` runs `flutter analyze`, `flutter test`, and `flutter build apk --debug`. Still missing: `composer audit`/`npm audit` as CI gates, and any actual deploy-on-push step (CI currently only verifies, it doesn't ship).

## Monitoring

**Not yet implemented.** No APM/error-tracking service (Sentry, Bugsnag, etc.) is wired in. The `security` log channel (`storage/logs/security.log`, daily rotation, 90-day retention) exists and should be shipped to a log aggregator/SIEM in production rather than left on local disk.
