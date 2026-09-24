# Deployment

## Status: not yet deployed anywhere

This document describes what production deployment *should* look like based on the application's actual requirements — it has not been executed. No staging/production environment, CI/CD pipeline, or hosting has been set up.

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
3. **Scheduler** (`php artisan schedule:run` via cron, once analytics aggregation or other scheduled jobs are added — none exist yet)

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

**Not yet implemented.** At minimum, production needs: automated encrypted Postgres backups on a schedule, a tested restore procedure (a backup that's never been restored isn't a backup — per the spec's own section 41), and a documented retention policy for `audit_logs`/`security_events`/`scans` (see the retention discussion the spec calls for in section 52 — not yet implemented as actual pruning jobs, only as a design intention).

## CI/CD

**Not yet implemented.** No `.github/workflows` or equivalent exists. A minimal first CI job should run `php artisan test`, `composer audit`, `npm audit`, and `vendor/bin/pint --test` (code style) on every push.

## Monitoring

**Not yet implemented.** No APM/error-tracking service (Sentry, Bugsnag, etc.) is wired in. The `security` log channel (`storage/logs/security.log`, daily rotation, 90-day retention) exists and should be shipped to a log aggregator/SIEM in production rather than left on local disk.
