# Analytics

## Current status: live counts only, no time-series or aggregation job

## What's built

The admin dashboard home page (`GET /admin/dashboard`) shows **live, real** `COUNT()` queries against the transactional tables: total/active users, total/active vehicles, total/available/assigned/revoked tags, new contact requests, unresolved security events. Every number is a real query result — none of it is mocked, per the spec's explicit "do not generate fake statistics" rule (verified: a fresh database shows all zeros, not placeholder sample data).

## What section 43/17 ask for that isn't built

- `analytics_daily_stats` pre-aggregation table exists in the schema (see `docs/DATABASE.md`) but **nothing populates it** — no scheduled job reads `scans`/`audit_logs` and rolls them into daily buckets yet.
- Time-series charts (scans over time, registrations, activations, contact requests) — no charting library is wired into the dashboard yet, and there's no aggregated data to chart even if there were.
- Date-range selection (today/7d/30d/90d/custom) on the dashboard.

## Why the dashboard currently queries live tables directly instead of the aggregate table

This is intentionally the *simple, obviously-correct* version for now rather than a half-built aggregation pipeline: querying `COUNT()` directly against `users`/`vehicles`/`tags` is correct and fast at current (near-zero) data volume. The spec's warning against overloading transactional tables with analytics queries becomes a real concern once these tables have meaningful row counts (thousands of scans, etc.) — the `analytics_daily_stats` table exists specifically so that migration path is already schema-ready, but building the scheduled aggregation job before there's real traffic to aggregate was judged lower priority than the features that generate the data in the first place.

## Recommended next step

A daily `php artisan schedule` job that reads yesterday's `scans` (grouped by `action_type`, `tag_id`'s `type`) and `audit_logs`/`security_events` counts, writes them into `analytics_daily_stats`, and a dashboard chart component that reads from that table with a date-range selector — at that point the live-count cards can stay as "today's snapshot" while the charts show history.
