# Security Audit

## Scope and nature of this document

This is a record of the **internal security review performed during initial development**, not a third-party penetration test or formal audit. No external security firm has reviewed this codebase. Treat every finding below as self-reported and re-verify independently before production launch.

## Findings during development, and their resolution

| # | Finding | Severity | Resolution |
|---|---|---|---|
| 1 | `OtpCode.consumed_at` missing from `$fillable` → OTP codes could be replayed indefinitely within their 5-minute validity window | **High** | Fixed; added to `$fillable`; regression test added (`OtpSecurityTest`) |
| 2 | `Admin.locked_until` missing from `$fillable` → the 5-attempt brute-force lockout never actually took effect | **High** | Fixed; regression test added (`AdminAuthSecurityTest`) |
| 3 | `Tag.revocation_reason`/`revoked_at` missing from `$fillable` → revocation reason silently lost (audit-trail integrity issue, not an access-control bypass) | Medium | Fixed; regression test added |
| 4 | Same pattern found in `TagBatch`, `ContactRequest`, `User`, `Device` — timestamps/notes silently not persisting | Low–Medium | Fixed across all six affected models after a systematic grep-based audit of every `->update()`/`->create()` call site against its model's `$fillable` |
| 5 | Base `Controller` class was missing `AuthorizesRequests` — every `$this->authorize()` call in the codebase was a fatal error, not a silent bypass | High (availability, not confidentiality — it broke rather than let unauthorized access through) | Fixed |
| 6 | Route-ordering bug: `GET /admin/batches/create` was matched by the `batches/{tagBatch}` wildcard (registered first), attempting to bind a `TagBatch` with id `"create"` and causing a Postgres type error | Low | Fixed by reordering route registration; documented inline in `routes/web.php` |
| 7 | Admin dashboard's own strict CSP (`script-src 'self'`) correctly blocked its own inline `onclick`/`<script>` handlers | N/A (control working as intended) | Rewrote the affected UI (mobile nav drawer, batch print button, tag revoke form) to avoid inline JS entirely, rather than weakening the CSP |
| 8 | Dashboard security-events counter included routine `AUTH_SUCCESS` info-level events in "unresolved" count, making every fresh login look like an unaddressed security issue | Low (UX/signal-quality, not a vulnerability) | Fixed — counter now filters to `warning`/`critical` severity only |
| 9 | Mobile: `phosphor_flutter` incompatible with the current Flutter/Dart SDK (`IconData` is now `final`), invisible to `flutter analyze`, only surfaced via an actual `flutter build apk` | Low (build-breaking, not a vulnerability) | Fixed — replaced with Flutter's bundled Material Icons |
| 10 | Mobile: a `Spacer()`-based login-screen layout overflowed on constrained viewport heights (real `RenderFlex overflowed` runtime error, caught by actually running the app, not by static analysis) | Low (UX, not a vulnerability, but would have shipped a visibly broken screen) | Fixed with a scrollable, `LayoutBuilder`-constrained layout that also handles the on-screen keyboard correctly |

## Verified controls (positive findings)

- IDOR/BOLA: confirmed blocked across vehicles and tags with two-account tests
- RBAC: confirmed a real `support_admin` test account is denied `/admin/admins` and `/admin/batches/create` (403) while retaining access to `/admin/contact-requests` (200) — tested both manually against the running app and via automated tests
- QR activation concurrency: confirmed safe under genuine parallel load (10 simultaneous HTTP requests, not just sequential test assertions)
- XSS: confirmed `<script>` input to public message fields is rejected, not sanitized-and-accepted

## Not yet reviewed

Anything in the "not implemented" list across the other docs (NFC provisioning, payments, mobile app, reporting exports) has no security review because it doesn't exist yet. A repeat of this review should happen once those features are built, not be assumed to inherit this document's conclusions.

## Recommendation

Commission an actual third-party security review before handling real customer PII or vehicle data in production. This document should be handed to that reviewer as a starting map of what's already been checked internally, not as a substitute for their work.
