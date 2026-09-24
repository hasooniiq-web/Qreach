# Testing

## Backend

```bash
cd backend
php artisan test
```

Tests run against an in-memory SQLite database (`phpunit.xml`), fully isolated from the Postgres dev/production database — safe to run anytime without affecting real data.

## Mobile

```bash
cd mobile
flutter analyze   # static analysis — clean except 2 harmless style infos
flutter test      # widget tests
flutter build apk --debug   # full compile to a real APK — catches issues analyze can't
```

The debug APK build matters as a distinct step: it caught a third-party package incompatibility (`phosphor_flutter`) that `flutter analyze` missed entirely. Treat "analyze is clean" and "it actually builds" as two separate claims — this project needed both to be checked.

## What's covered (49 tests, all passing as of the last commit)

| File | Covers |
|---|---|
| `tests/Feature/Api/TagActivationTest.php` | Successful activation, duplicate-activation rejection, cross-user IDOR on activation, revoked-tag rejection, unknown-token handling, revocation reason/timestamp persistence |
| `tests/Feature/Api/VehicleAuthorizationTest.php` | IDOR on view/update/delete, list scoping to the authenticated user only |
| `tests/Feature/Api/OtpSecurityTest.php` | OTP replay prevention (the bug found and fixed during development) |
| `tests/Feature/Admin/AdminAuthSecurityTest.php` | Admin brute-force lockout (also a found-and-fixed bug) |
| `tests/Feature/Admin/RbacTest.php` | Per-permission route gating across three role scenarios, plus the zero-role default-deny case |
| `tests/Feature/Admin/AnalyticsTest.php` | Aggregation command computes real counts, reruns are idempotent (no duplicate rows), page requires permission, renders with no fabricated data |
| `tests/Feature/Admin/NfcTagManagementTest.php` | NFC batch creation reuses the QR lifecycle, tag-type filtering, print action hidden for NFC batches |
| `tests/Feature/Admin/SystemSettingTest.php` | Settings changes actually affect service behavior (not just stored), permission gating, out-of-range rejection |
| `tests/Feature/BackupCommandTest.php` | Backup command refuses to run without an encryption key configured |
| `tests/Feature/Public/ScanTest.php` | Public page access without auth, generic-invalid-page for any failure reason, XSS rejection, emergency confirmation gating, contact-request creation without phone-number leakage, rate limiting |
| `tests/Feature/Security/AdditionalSecurityTest.php` | OTP brute-force lockout after max attempts, deleted-vehicle scan handling, property-level authorization on vehicle updates (can't reassign ownership via payload), export/batch-creation authorization bypass by direct URL, session token invalidation after logout |

## What's *not* covered by automated tests yet

- Genuine multi-process concurrency (the activation race was verified manually with 10 real parallel HTTP requests via `curl`, documented in `QR_SECURITY.md` — the automated test suite only exercises the *sequential* logic, since PHPUnit runs single-threaded against a shared SQLite connection)
- Admin dashboard view rendering beyond route-level status codes (no Dusk/browser-based UI tests)
- Load/performance testing
- The mobile app on an actual Android/iOS device or emulator (none was available in this environment — verification was `flutter analyze` + `flutter build apk --debug` + `flutter test` + manual interaction via a web build in a browser)

## How bugs were actually found in this project

Worth recording honestly: several real bugs (OTP replay, admin lockout, tag revocation reason, several other silently-dropped `$fillable` fields) were **not** caught by writing tests first — they were found by manually exercising the running application (via the browser, then via `curl` for scriptable verification) and checking the database afterward, because Eloquent's mass-assignment guard fails *silently* rather than throwing, so a test asserting "the request succeeded" would have passed even with the bug present. Regression tests were written immediately after each fix specifically to catch the silent-failure pattern (asserting the actual persisted column value, not just the HTTP status code).

## Recommended before production

- CI now runs `php artisan test` on every push (`.github/workflows/backend-ci.yml`), plus a separate job that runs real migrations against a Postgres service container (SQLite is used for the test run itself, but migration compatibility with Postgres is checked separately, since the two aren't guaranteed to behave identically). `.github/workflows/mobile-ci.yml` does the equivalent for Flutter (`flutter analyze`, `flutter test`, `flutter build apk --debug`).
- `composer audit` and `npm audit` as CI gates — not yet added.
- A genuine concurrency test harness (e.g., spinning up parallel Guzzle requests within a single PHPUnit test using a real Postgres connection, not SQLite) to keep the manual concurrency verification from silently rotting.
