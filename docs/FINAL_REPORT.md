# Final Report — Qreach / SmartTag

This is the honest final report required by section 60 of the original build
specification. It does not claim completion where none was verified. Every
"done" claim below was checked by actually running the relevant command
(tests, builds, migrations) in this environment; every "not done" claim is
stated plainly rather than implied to be done. Where a topic has its own
deep-dive document, this report summarizes and links to it rather than
duplicating it — treat those documents as the source of truth for detail.

## 1. Architecture

Laravel 12 (PHP) backend + PostgreSQL 18, serving three surfaces from one
codebase: an Arabic-first server-rendered admin dashboard (Blade + Tailwind
v4 via Vite), a public no-auth QR/NFC scan experience (also Blade), and a
JSON API (`/api/v1/*`, Sanctum-authenticated) consumed by a separate Flutter
mobile app (`mobile/`). See `docs/ARCHITECTURE.md` for request-flow diagrams
of the two security-critical paths (QR activation, public scan) and
`docs/ARCHITECTURE_DECISIONS.md` for the reasoning behind non-obvious choices
(Laravel 12 over an older pinned version, hand-rolled RBAC instead of a
package, app name "Qreach" vs. product name "SmartTag").

## 2. Technology stack

- Backend: Laravel 12, PHP, PostgreSQL 18, Sanctum (API tokens), Tailwind v4
  + Vite, Chart.js (self-hosted, not CDN — required by the CSP).
- Mobile: Flutter/Dart, Riverpod, go_router, Dio, flutter_secure_storage,
  mobile_scanner.
- CI: GitHub Actions (`.github/workflows/backend-ci.yml`,
  `.github/workflows/mobile-ci.yml`).

## 3. Database schema

24 migrations, 24 Eloquent models. See `docs/DATABASE.md` for the identifier
strategy (UUIDs as route keys, auto-increment internally), the generic
`tags`/`qr_tags`/`nfc_tags` abstraction, and why `sessions.user_id` holds an
admin id (a documented Laravel framework constraint, not a bug).

## 4. API inventory

66 registered routes across admin web routes, public scan routes, and the
versioned JSON API. Full endpoint list with request/response shapes:
`docs/API.md`.

## 5. Authentication architecture

Two independent guards: `admin` (session-based, email+password, Argon2id
hashing) and customers (phone + OTP via Sanctum tokens, no password column
at all). OTP codes are never stored in plaintext — only a bcrypt/Argon2id
hash of the code, so a database dump alone can't recover a valid code.
Registration and login share one endpoint pair specifically so an
unrecognized phone number doesn't leak account existence (no enumeration
oracle). Detail: `docs/SECURITY.md`.

## 6. Authorization / RBAC

Hand-rolled roles/permissions (not a package) with default-deny middleware —
an admin with zero assigned roles is denied on every permission-gated route,
verified by `RbacTest::test_an_admin_with_no_roles_is_denied_everywhere`.
Every admin route is gated by a specific permission slug, not just
"authenticated as admin" — verified at the route level for management,
QR/batch, contact-request, analytics, settings, and NFC areas, including
direct-URL bypass attempts (a support-role admin hitting the export/create
POST endpoints directly, not just failing to see the link in the UI — see
`tests/Feature/Security/AdditionalSecurityTest.php`).

## 7. QR architecture

Cryptographically random tokens (`random_bytes`), one-time atomic activation
using a DB transaction with row locking (`lockForUpdate`) plus a conditional
`UPDATE ... WHERE status = 'available'` with an affected-row-count check —
this is what actually makes concurrent activation attempts safe, not just
the transaction wrapper alone. Verified with 10 real parallel HTTP requests
via `curl` (not simulated) racing to activate the same tag; exactly one
succeeded. Full design: `docs/QR_SECURITY.md`.

## 8. NFC architecture

NFC tags reuse the same `tags`/lifecycle abstraction as QR (one polymorphic
lifecycle, not a parallel implementation) and have full admin batch
creation, listing, and filtering. **What's not done: physical chip
provisioning** — no NFC hardware was available in this environment, so
writing the token to an actual chip and reading it back has never been
tested end to end. See `docs/NFC_SECURITY.md` for exactly what's simulated
vs. real.

## 9. Mobile architecture

Flutter app with phone/OTP login, vehicle CRUD, QR camera-scan activation,
and profile/settings screens, using Riverpod for state and go_router for
auth-guarded navigation. **Verified**: `flutter analyze` (clean except 2
harmless style infos), `flutter test`, and — critically — `flutter build apk
--debug`, which caught a real build-time-only bug (`phosphor_flutter`'s
`IconData` subclass breaking under Dart's newer `final class` semantics)
that static analysis missed entirely. The app was also run and manually
exercised via `flutter run -d web-server` in a real browser during
development (a `RenderFlex` overflow bug was found and fixed this way, not
by analysis). **Never run on an actual Android/iOS device or emulator** —
none was available in this environment.

## 10. Dashboard architecture

Server-rendered Blade admin dashboard, Arabic-first with RTL layout,
Tailwind v4 design tokens, sections for users, vehicles, QR/NFC tags and
batches, contact requests, analytics, system settings, security events,
audit logs, and admin/role management.

## 11. UI/UX design system

Brand colors restricted to `#FFC107`/`#000000`/`#FFFFFF` as required,
IBM Plex Sans Arabic typography, RTL-first layout. Detail and known gaps
(no dedicated design tool mockups, no accessibility audit tool run):
`docs/UI_UX_DESIGN_SYSTEM.md`.

## 12. Analytics architecture

A real daily aggregation command (`analytics:aggregate`, scheduled via
`Schedule::command(...)->dailyAt(...)`) reads `scans`, `users`,
`tag_assignments`, and `contact_requests` and writes rollups into
`analytics_daily_stats`, which the dashboard's chart page reads exclusively
(never live tables). The aggregation is genuinely idempotent — this was not
true on the first implementation (`updateOrCreate()` silently duplicated
rows on a rerun due to a date-cast query-matching bug); the fix and its
verification (11 rows after one run, 11 after two) are documented in
`docs/ANALYTICS.md`.

## 13. Security controls

Argon2id password hashing, CSPRNG tokens throughout, OTP codes hashed not
stored plaintext, CSP with `script-src 'self'` and no `unsafe-inline`
(enforced hard enough that it drove real implementation changes — Chart.js
had to be self-hosted, not loaded from a CDN, to comply), rate limiting on
every public and auth-sensitive endpoint, structured audit logging separate
from security-event logging. Full inventory: `docs/SECURITY.md` and
`docs/SECURITY_AUDIT.md` (the latter documents real findings from
development, not a hypothetical checklist).

## 14. OWASP mapping

See `docs/OWASP_MAPPING.md` for the full OWASP Top 10:2025 / API Security
Top 10 / MASVS mapping. Not an external audit — a self-assessment against
the actual implementation.

## 15. MASVS mapping

Covered within `docs/OWASP_MAPPING.md` and `docs/MOBILE_SECURITY.md`. Mobile
MASVS compliance is assessed against a codebase that has **not been run on
real hardware** — treat mobile security claims as "verified in code and via
web-build testing," not "verified on-device."

## 16. Threat model

`docs/THREAT_MODEL.md` — actors, assets, and per-threat mitigation status,
including an explicit "not yet modeled" section for what wasn't built.

## 17. Testing results

**90/90 backend tests pass** (`php artisan test`), covering IDOR/BOLA,
OTP replay and brute force, admin RBAC including direct-URL bypass attempts,
QR duplicate/revoked/concurrent activation, public-endpoint rate limiting,
XSS rejection, analytics aggregation idempotency, backup safety checks, and
session-token invalidation after logout. `vendor/bin/pint --test` passes
(code style). Flutter: `flutter analyze` clean (2 style infos),
`flutter test` passes, `flutter build apk --debug` succeeds. Full breakdown
and — importantly — an honest account of what's *not* covered (genuine
multi-process concurrency in the automated suite, mobile on-device testing,
load testing): `docs/TESTING.md`.

Two real bugs were found and fixed *while writing this final report's
security-test pass*, not before: the ownership-hijack regression test
initially failed with a 404 because it used the wrong route-key field (a
test bug, not a product bug), and the logout-token-reuse test initially
passed for the wrong reason (Laravel's auth guard caches the resolved user
per guard instance, which persists across simulated requests within one
PHPUnit method — not a real vulnerability, but worth documenting since it
could mask a real one). Both are explained in the commit history and in
`AdditionalSecurityTest.php`'s docblocks.

## 18. Performance results

**Not benchmarked.** No load testing, no `EXPLAIN ANALYZE` review of
production-scale query plans, no query-count profiling under realistic data
volumes. The schema uses indexes on the columns that matter for the hot
paths (tag lookup by token, vehicle lookup by user), and analytics
deliberately reads from a pre-aggregated table rather than live tables to
avoid the N+1/unbounded-query failure mode the spec warns about — but none
of this has been measured under load.

## 19. Deployment architecture

**Not deployed anywhere.** `docs/DEPLOYMENT.md` describes the target
configuration (env vars, required processes, build steps) but no
staging/production environment or hosting exists. CI (test/build
verification) exists and passes on every push; that is distinct from a
deployment pipeline, which does not exist — CI verifies, it does not ship.

## 20. Backup / DR

**Implemented and tested**, not just designed: `php artisan backup:run`
produces an AES-256-CBC-encrypted `pg_dump`; `php artisan backup:restore`
restores it using a separate least-privilege backup credential (kept apart
from the app's runtime DB role, which deliberately lacks `CREATEDB`). This
was verified with a real backup → restore → row-count-and-content-comparison
→ cleanup cycle. Still missing: an actual cron schedule invoking the backup
command in production, and a documented retention/rotation policy. Detail:
`docs/DISASTER_RECOVERY.md`.

## 21. Remaining risks

- **No payment gateway integration.** Billing schema (`subscription_plans`,
  `subscriptions`, `payments`) exists; no PSP is wired up because none was
  specified. Launching any paid tier today is not possible.
- **SMS delivery is a log-only stub** (`LogSmsGateway`) — it actively refuses
  to run when `APP_ENV=production`, so this fails loudly rather than
  silently, but a real SMS provider must be integrated before OTP delivery
  works outside development.
- **Push notifications never reach a device** — `Device` records store push
  tokens, but no FCM/APNs credentials are wired up; notifications currently
  only land in the database.
- **No external penetration test.** All security testing to date is
  self-assessment plus automated regression tests written against
  real, reproduced bugs — valuable, but not a substitute for adversarial
  external review before handling real user data at scale.
- **Mobile app has never run on physical hardware or an emulator.**
- **NFC has never touched a physical chip.**

## 22. Known limitations

- General reporting system (section 18 of the spec) is not built — only a
  single QR-batch CSV export and printable sheet exist. See
  `docs/REPORTING.md`.
- No anonymization/retention pruning job for raw `scans`/`audit_logs` rows
  — they accumulate indefinitely today; only the daily analytics rollup
  reduces reliance on querying them directly.
- No `composer audit`/`npm audit` CI gate yet.
- No APM/error-tracking service (Sentry or equivalent) wired in.

## 23. Features completed

Customer OTP auth (phone-only, hashed codes, replay-proof, brute-force
locked), vehicle CRUD with strict per-user authorization, QR/NFC batch
generation with cryptographically random tokens, one-time concurrency-safe
activation, tag revocation, the full public no-auth scan experience
(vehicle-block/lights/message/emergency/contact-owner actions, each
rate-limited and audit/security-event logged), the Arabic RTL admin
dashboard (users, vehicles, tags/batches, contact requests, RBAC-gated
admin/role management, audit logs, security events), a real analytics
aggregation pipeline with charted dashboard, a system settings screen wired
to real service behavior, encrypted database backup/restore, CI for both
backend and mobile, and a Flutter customer mobile app covering the same
customer-facing flows.

## 24. Features partially completed

- **NFC**: full admin lifecycle management; no physical hardware
  provisioning or read-back.
- **Notifications**: created and queued correctly (fixed a real bug where
  they were sending synchronously); never reach an actual device push.
- **Reporting**: one export exists; the general system does not.
- **Mobile app**: functionally complete for the built flows; never verified
  on real hardware.

## 25. Features not implemented

Updated after the later work (Basic/Plus plans, visitor conversations,
server-side FCM push, retention job):

- **Done since the original report:** push delivery through FCM HTTP v1
  (needs the service-account key, verified against real FCM with one test
  push); retention/anonymization job (`data:prune`, scheduled daily);
  Basic/Plus plans with server-enforced visibility; two-way visitor
  conversations that auto-delete 15 days after the last message.
- **Still not done:** payment gateway (Plus is granted by an admin or through
  an upgrade request), real SMS gateway (OTP codes are only written to the log
  by `LogSmsGateway`), APNs/iOS push, dark mode in the mobile app, general
  reporting system beyond the exports, production deployment, external
  penetration test, app-store submission, and verification on a range of real
  devices (the app has been built and run on one phone only).
- **Needs a decision before launch:** which SMS provider and which payment
  gateway; a public `APP_URL` (QR codes and NFC chips encode it — changing it
  later means re-printing/re-writing them).
