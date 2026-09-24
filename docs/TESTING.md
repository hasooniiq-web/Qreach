# Testing

## Running the suite

```bash
cd backend
php artisan test
```

Tests run against an in-memory SQLite database (`phpunit.xml`), fully isolated from the Postgres dev/production database — safe to run anytime without affecting real data.

## What's covered (32 tests, all passing as of the last commit)

| File | Covers |
|---|---|
| `tests/Feature/Api/TagActivationTest.php` | Successful activation, duplicate-activation rejection, cross-user IDOR on activation, revoked-tag rejection, unknown-token handling, revocation reason/timestamp persistence |
| `tests/Feature/Api/VehicleAuthorizationTest.php` | IDOR on view/update/delete, list scoping to the authenticated user only |
| `tests/Feature/Api/OtpSecurityTest.php` | OTP replay prevention (the bug found and fixed during development) |
| `tests/Feature/Admin/AdminAuthSecurityTest.php` | Admin brute-force lockout (also a found-and-fixed bug) |
| `tests/Feature/Admin/RbacTest.php` | Per-permission route gating across three role scenarios, plus the zero-role default-deny case |
| `tests/Feature/Public/ScanTest.php` | Public page access without auth, generic-invalid-page for any failure reason, XSS rejection, emergency confirmation gating, contact-request creation without phone-number leakage, rate limiting |

## What's *not* covered by automated tests yet

- Genuine multi-process concurrency (the activation race was verified manually with 10 real parallel HTTP requests via `curl`, documented in `QR_SECURITY.md` — the automated test suite only exercises the *sequential* logic, since PHPUnit runs single-threaded against a shared SQLite connection)
- Admin dashboard view rendering beyond route-level status codes (no Dusk/browser-based UI tests)
- Load/performance testing
- The Flutter app (doesn't exist yet)

## How bugs were actually found in this project

Worth recording honestly: several real bugs (OTP replay, admin lockout, tag revocation reason, several other silently-dropped `$fillable` fields) were **not** caught by writing tests first — they were found by manually exercising the running application (via the browser, then via `curl` for scriptable verification) and checking the database afterward, because Eloquent's mass-assignment guard fails *silently* rather than throwing, so a test asserting "the request succeeded" would have passed even with the bug present. Regression tests were written immediately after each fix specifically to catch the silent-failure pattern (asserting the actual persisted column value, not just the HTTP status code).

## Recommended before production

- A CI job running `php artisan test` on every push (no CI pipeline exists yet — see `DEPLOYMENT.md`)
- `composer audit` and `npm audit` as CI gates
- A genuine concurrency test harness (e.g., spinning up parallel Guzzle requests within a single PHPUnit test using a real Postgres connection, not SQLite) to keep the manual concurrency verification from silently rotting
