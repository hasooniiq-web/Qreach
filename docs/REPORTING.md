# Reporting

## Current status: one export exists; the general reporting system (section 18) does not

## What's actually built

- **QR batch CSV export** (`GET /admin/batches/{batch}/export`, `qr.export` permission required, audit-logged as `qr.batch_exported`): streams `serial_number, public_token, public_url, status, type` for every tag in a batch.
- **QR batch printable sheet** (`GET /admin/batches/{batch}/print`): a grid of rendered QR PNGs with serial numbers, styled for physical printing.

## What section 18 asks for that isn't built

User report, vehicle report, QR report (beyond the one batch export above), NFC report, scan report, contact-request report, notification report, security report, audit report, activation report — each with filters, date ranges, pagination, sorting, CSV/Excel/PDF export, and async/queued generation for large exports.

## Recommended approach when this is built

- Follow the same pattern already established for the batch export: a dedicated controller action, a specific permission (`reports.export` already exists in the seeded RBAC permission set — see `RolesAndPermissionsSeeder`), and an `AuditLogger::log('report.exported', ...)` call so every export is traceable, per section 18's "every export must be authorized and auditable."
- Large exports should be queued jobs that email/notify the admin a download is ready, not synchronous requests — the existing queue worker infrastructure (already required for notifications) can carry this load without new infrastructure.
- Reports should read from `analytics_daily_stats` where possible rather than aggregating raw `scans`/`audit_logs` at request time (see `docs/DATABASE.md`) — but that aggregation table isn't populated yet either (see `docs/ANALYTICS.md`), so building reports and building the aggregation job are coupled work.
