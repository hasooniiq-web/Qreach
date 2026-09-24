# Analytics

## Current status: real aggregation pipeline with a scheduled job and charted dashboard

## What's built

- `php artisan analytics:aggregate [--date=]` (`App\Console\Commands\AggregateAnalytics`) reads `scans` (grouped by `action_type`), `users` (new registrations), `tag_assignments` (activations), and `contact_requests` for a given day, and writes one row per metric into `analytics_daily_stats`. Scheduled daily via `bootstrap/app.php`'s `Schedule::command(...)->dailyAt(...)`.
- The write is idempotent by design: it uses a raw `DB::table(...)->where(...)->update()` followed by a conditional `insert()` rather than `updateOrCreate()`. This was a deliberate fix — the first implementation used `updateOrCreate()` and silently produced duplicate rows on a rerun because Eloquent's date-cast query normalization didn't match the stored value on the second lookup. Re-running the command for the same day now genuinely produces the same row count both times (verified: 11 rows after one run, 11 after two, not 22).
- `GET /admin/analytics` (`AnalyticsController`) reads exclusively from `analytics_daily_stats` — never the live transactional tables — for five metrics (scans, registrations, QR activations, contact requests, notifications), with a period selector (today/7d/30d/90d/custom range) and a chart per metric.
- Charts are rendered with Chart.js, self-hosted via npm/Vite (not a CDN), reading data from `data-chart` HTML attributes rather than inline `<script>` — required because the app's CSP is `script-src 'self'` with no `unsafe-inline`.
- The admin dashboard home page (`GET /admin/dashboard`) still shows separate live `COUNT()` snapshot cards against the transactional tables (total/active users, tags by status, unresolved security events) — this is unchanged and intentional: a live "right now" snapshot next to a historical, aggregated chart page.
- Regression tests: `tests/Feature/Admin/AnalyticsTest.php` covers that aggregation computes real counts from actual scan rows, that rerunning aggregation for the same day does not duplicate rows, and that the page requires permission and renders with no fabricated data on an empty database.

## What section 43/17 still ask for that isn't built

- Anonymization/aggregation beyond daily rollups (e.g. discarding IP-level detail after N days) — `scans`/`audit_logs` retain raw rows indefinitely today; see `docs/DATA_RETENTION` topics in the master spec and `docs/DISASTER_RECOVERY.md` for what's covered vs. not.
- Per-tag or per-vehicle drill-down analytics (the current dashboard is platform-wide only).
- Export of analytics data (CSV/PDF) — see `docs/REPORTING.md`.

## Recommended next step

Add a retention/anonymization job for raw `scans` rows older than a configurable window, now that the daily rollup means the detail rows are no longer the only source of historical counts.
