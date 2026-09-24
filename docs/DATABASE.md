# Database

PostgreSQL, 24 migrations, all under `backend/database/migrations/`. This document explains the non-obvious design decisions — for exact columns, read the migration files, which are the source of truth and are kept short and commented only where the *why* isn't obvious from the schema itself.

## Identifier strategy

Every publicly-referenceable table has two identifiers:

- An internal auto-increment `id` (bigint) — used for foreign keys, joins, indexes. **Never sent to a client.**
- A `uuid` (or, for tags, a separate high-entropy `public_token`) — used in URLs and API responses. Models override `getRouteKeyName()` so Laravel's route-model binding resolves by the public identifier automatically (e.g. `Vehicle::getRouteKeyName() → 'uuid'`, `Tag::getRouteKeyName() → 'public_token'`).

This means a client can never enumerate vehicles or tags by guessing sequential integers.

## The generic tag abstraction (`tags`, `qr_tags`, `nfc_tags`)

Section 12 of the spec asks for QR and NFC to share a lifecycle while remaining independently revocable. The schema models this as:

- `tags` — the shared parent: `public_token`, `serial_number`, `type` (`qr`/`nfc`), `status`, `vehicle_id`, lifecycle timestamps, `replaced_by_tag_id` (self-referencing, for replacement history).
- `qr_tags` / `nfc_tags` — 1:1 child tables holding type-specific data (QR: error-correction level, rendered image paths; NFC: chip UID, provisioning status).

`tag_assignments` is a separate append-only history table — `tags.vehicle_id` only holds the *current* assignment, while `tag_assignments` records every assign/reassign/revoke/replace event with who did it and why.

## Why `sessions.user_id` holds an admin id

`0001_01_01_000000_create_users_table.php` keeps a column literally named `user_id` on the `sessions` table even though only admins use database-backed sessions in this app (customers authenticate via Sanctum tokens, not sessions). This isn't a naming mistake — Laravel's `DatabaseSessionHandler` hardcodes that column name regardless of which guard is active, and renaming it (as an earlier draft of this schema did) breaks every session write with a `column "admin_id" does not exist` error. Documented here so a future contributor doesn't "fix" it back into a bug.

## Customers have no password column

`users.password` doesn't exist. Authentication is phone + OTP only (`otp_codes` table, HMAC-hashed codes, never plaintext) — see `docs/QR_SECURITY.md`'s sibling discussion in `SECURITY.md` for the OTP flow. Admins (`admins` table) do have `password` (Argon2id) plus optional TOTP fields, since the dashboard needs stronger, session-based auth for privileged operations.

## RBAC tables

`roles`, `permissions`, `role_permission`, `admin_role` — a small hand-rolled RBAC rather than a package like `spatie/permission`, because the spec explicitly separates "admin" as a distinct actor type from customer `users`, and a generic multi-model permission package adds indirection for a system with exactly one privileged actor type. Roles seeded: `super_admin`, `security_admin`, `operations_admin`, `support_admin`, `reports_analyst` (see `RolesAndPermissionsSeeder`).

## Analytics tables are separate from transactional tables

`scans` is the append-only raw event log (every public tag view/action). `analytics_daily_stats` is a pre-aggregated rollup table intended to be populated by a scheduled job — dashboard charts should read from the aggregate table, never run `COUNT()`/`GROUP BY` against `scans` directly at request time, per section 43 of the spec. **Status: the aggregation job itself is not yet implemented** — the table exists, nothing populates it yet.

## Billing tables exist; billing logic doesn't

`subscription_plans`, `subscriptions`, `payments` are schema-complete (including an `idempotency_key` on `payments` for safe webhook handling) but no payment gateway is integrated — the spec names no PSP, currency requirement, or pricing model, so this was deliberately left as a foundation rather than guessed. See `ARCHITECTURE_DECISIONS.md`.
