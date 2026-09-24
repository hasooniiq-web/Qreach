# Security Controls

This document maps each security requirement in the spec to where it's actually implemented. Where something is a documented gap rather than an implemented control, it's marked **NOT IMPLEMENTED**.

## Authentication

- **Customers**: phone + OTP only, no password (`app/Services/OtpService.php`). OTP is 6 digits, `random_int` (CSPRNG), hashed with the same algorithm as passwords (Argon2id via `Hash::make`) before storage — never plaintext. 5-minute expiry, max 5 verify attempts, 60-second resend cooldown enforced at the service layer *and* independently by rate limiters (defense in depth).
- **Admins**: email + password (Argon2id) + optional TOTP (`pragmarx/google2fa-laravel`). Failed-attempt lockout: 5 failures → 15-minute lock (`app/Http/Controllers/Admin/AuthController.php`). Forced password change on first login for the bootstrap admin.
- **Mobile sessions**: Sanctum personal access tokens, one per device, linked to a `devices` row for inventory/revocation. `logout` revokes one device; `logout-all` revokes every token for the account.

## Authorization

- Every customer-facing resource (`Vehicle`, and by extension its `Tag`s) is checked against `auth()->id()` via a Laravel Policy (`VehiclePolicy`) — never trusts a client-supplied ID alone. Verified with automated IDOR tests (`tests/Feature/Api/VehicleAuthorizationTest.php`, `tests/Feature/Api/TagActivationTest.php`).
- Every admin route is gated by a specific permission string (`admin.permission:{key}` middleware), not just "is an admin." Default deny: an admin with zero roles gets 403 on every protected route (`tests/Feature/Admin/RbacTest.php`).

## Rate limiting

Defined centrally in `app/Providers/AppServiceProvider.php::registerRateLimiters()`. Every limiter combines at least two dimensions (e.g., IP *and* phone/token/account) — never IP-only, since IP-only limits are trivially bypassed. Limiters exist for: OTP request/verify, admin login, QR activation, public scan page views, public actions, emergency actions, contact requests, report exports.

## Input sanitization

Free-text fields reachable from an unauthenticated visitor (`important_message`, `emergency` message, `contact_owner` message/name) go through `App\Rules\PlainTextOnly`, which **rejects** (doesn't silently strip) any input containing HTML tags, `javascript:`/`data:` URIs, or `on*=` attributes. Verified with an automated XSS-attempt test.

## Secrets and logging

- `AuditLogger` redacts any metadata key containing `password`, `otp`, `code`, `token`, `secret` before writing to `audit_logs`.
- OTP plaintext is never logged in production (`LogSmsGateway` — the dev-only SMS stand-in — refuses to run when `APP_ENV=production`).
- Security-relevant events (`SecurityEventLogger`) go to both the database (`security_events`) and a dedicated `security` log channel, independent of the general application log.

## Transport & headers

`App\Http\Middleware\SecurityHeaders` sets on every response: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (denies geolocation/mic/camera), `Content-Security-Policy` (`script-src 'self'` — no inline scripts anywhere in the app, including the admin dashboard's own nav JS, which was rewritten to an external file specifically to satisfy this), and HSTS when served over HTTPS or in production.

Session cookies: `httponly`, `samesite=strict`, `secure` unless `APP_ENV=local`.

## Password/credential hygiene

- Argon2id is the configured hash driver (`config/hashing.php`), confirmed available on the target PHP build.
- The Postgres application role (`smarttag_app`) is a least-privilege role, separate from the `postgres` superuser used only for provisioning.
- No secret, password, or API key is committed to the repository; `.env` is gitignored.

## Known gaps (not implemented)

- **Push notification delivery** — FCM/APNs credentials aren't configured; notifications currently land only in the `notifications` database table, not on a device. See `MOBILE_SECURITY.md`.
- **Payment processing** — no PSP integrated (see `ARCHITECTURE_DECISIONS.md`).
- **NFC provisioning flow** — schema exists, no admin UI or provisioning endpoint yet.
- **Certificate pinning** (mobile) — not applicable yet, no mobile app exists.
- **Formal penetration test / third-party security audit** — not performed; this document reflects internal review only.
- **Dependency/secret scanning in CI** — no CI pipeline exists yet (see `DEPLOYMENT.md`).
