# OWASP Mapping

## OWASP Top 10:2025

| Category | Architecture control | Implementation | Test |
|---|---|---|---|
| A01 Broken Access Control | Policies for every owned resource; RBAC middleware for every admin route | `VehiclePolicy`, `EnsureAdminHasPermission` | `VehicleAuthorizationTest`, `RbacTest` |
| A02 Security Misconfiguration | Centralized security headers, strict CORS allow-list (no wildcard), CSP with no `unsafe-inline` script | `SecurityHeaders` middleware, `config/cors.php` | Manual verification (CSP violations surfaced and fixed during dev — see commit history) |
| A03 Software Supply Chain Failures | Composer's built-in advisory audit blocked Laravel 11 outright; project runs current stable | `composer.json` pinned to `^12.0` | `composer audit` (no CI job yet — gap) |
| A04 Cryptographic Failures | Argon2id password hashing; CSPRNG for all tokens (`random_bytes`, `random_int`); no custom crypto | `config/hashing.php`, `TagTokenGenerator`, `OtpService` | — |
| A05 Injection | Eloquent/query builder parameterization throughout (no raw string-interpolated SQL anywhere in the codebase); `PlainTextOnly` rule for HTML/script injection in free-text fields | — | `ScanTest::test_important_message_rejects_html_content` |
| A06 Insecure Design | One-time QR activation modeled as an atomic, lockable state transition from the start, not bolted on; public/private data separation built into the schema (`Vehicle::publicAttributes()`) | `TagLifecycleService` | Concurrency test (10 parallel requests) |
| A07 Authentication Failures | OTP replay prevention, admin lockout, no password reuse across guards | `OtpService`, `Admin::isLocked()` | `OtpSecurityTest`, `AdminAuthSecurityTest` |
| A08 Software/Data Integrity Failures | DB transactions + row locks for all multi-step state changes (activation, revocation, replacement) | `TagLifecycleService` | Concurrency test |
| A09 Security Logging and Alerting Failures | `AuditLogger` (redacts secrets) + `SecurityEventLogger` (dedicated log channel + DB table) on every sensitive action | Both services, wired into auth/tag/admin flows | — |
| A10 Mishandling of Exceptional Conditions | Structured JSON error codes for the API; generic public "invalid tag" page regardless of the underlying reason (no information leak via error variance) | `bootstrap/app.php` exception renderers, `ScanController::resolveActiveTag` | — |

## OWASP API Security Top 10

| Category | Status |
|---|---|
| API1 Broken Object Level Authorization | Enforced via Policy on every vehicle/tag route; tested with two-account IDOR tests |
| API2 Broken Authentication | Sanctum tokens, OTP with replay/brute-force protection |
| API3 Broken Object Property Level Authorization | API Resources (`VehicleResource`, `TagResource`, `UserResource`) explicitly whitelist returned fields — internal `id`/`uuid` never serialized for `Tag` |
| API4 Unrestricted Resource Consumption | Rate limiters on every write endpoint and on OTP/activation specifically |
| API5 Broken Function Level Authorization | Admin permission middleware per route, not per-controller blanket checks |
| API6 Unrestricted Access to Sensitive Business Flows | QR activation and emergency actions both rate-limited and (for activation) concurrency-safe |
| API7 Server Side Request Forgery | No server-initiated fetch of user-supplied URLs exists in this codebase |
| API8 Security Misconfiguration | See A02 above |
| API9 Improper Inventory Management | Single versioned prefix (`/api/v1`), documented in `docs/API.md`; no undocumented/shadow endpoints |
| API10 Unsafe Consumption of APIs | This app doesn't currently consume any third-party API that processes untrusted data (the dev SMS gateway just logs) |

## OWASP MASVS

Not applicable yet — no mobile app has been built. Will need its own MASVS review once the Flutter app exists (secure storage for tokens, certificate handling, platform-appropriate biometric/PIN gating, etc.).
