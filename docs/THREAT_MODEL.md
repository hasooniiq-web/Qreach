# Threat Model

## Actors

| Actor | Access |
|---|---|
| Customer | Own account, own vehicles/tags via mobile API |
| Public visitor | No account; interacts only via `/t/{token}` |
| Admin | Dashboard, scoped by RBAC role |
| Malicious/automated attacker | Any public surface: OTP endpoints, public scan endpoints, admin login |
| Compromised device | A stolen phone with a valid Sanctum token still on it |
| Insider (admin abuse) | A legitimate but malicious/careless admin account |

## Assets and where they're protected

| Asset | Protection |
|---|---|
| Customer phone number | Never returned to the public visitor; visible only to the owner and admins with `users.view` |
| Vehicle owner's real contact info | `contact_methods.privacy_state` gates exposure; the public scan page's "contact owner" flow never surfaces the phone number |
| QR/NFC public tokens | 256-bit random (`random_bytes(32)`), uniqueness enforced at the DB level, never derived from user/vehicle IDs |
| Emergency contacts | No API/UI surface exposes these publicly at all (feature not yet built beyond schema) |
| Admin credentials | Argon2id hash, lockout after 5 failures, optional TOTP |
| Audit trail | Append-only table, no update/delete path in application code |

## Threats considered, and current mitigation status

| Threat | Mitigation | Status |
|---|---|---|
| QR token enumeration/guessing | 256-bit token space; unknown/revoked/wrong-status tokens render an identical generic page; `QR_GUESSING` security event logged per failed lookup | Implemented, tested |
| Concurrent/duplicate QR activation | DB transaction + row lock + conditional UPDATE with affected-row check | Implemented, load-tested (10 concurrent requests → 1 success) |
| OTP replay | `consumed_at` marks a code used after first successful verify | Implemented — **found and fixed a real bug here**: `OtpCode.consumed_at` was initially missing from the model's `$fillable`, so the write was silently dropped and replay was actually possible until fixed; see commit history and `tests/Feature/Api/OtpSecurityTest.php` |
| OTP brute force | 5 attempts/code, then the code is dead; rate limits on request+verify endpoints | Implemented |
| Admin brute force | 5 failed attempts → 15-minute lock | Implemented — **also initially broken** the same way (`Admin.locked_until` missing from `$fillable`); fixed, regression-tested |
| Account takeover via vehicle/tag IDOR | Ownership checked server-side via Policy on every route, verified with automated tests using two distinct accounts | Implemented, tested |
| Privilege escalation via admin RBAC | Every admin route requires an explicit permission string; zero-role admin denied everywhere | Implemented, tested |
| XSS via public message fields | `PlainTextOnly` validation rule rejects (not strips) HTML/script content | Implemented, tested |
| Mass scraping of vehicle/contact data | No enumerable listing endpoint exists for vehicles/tags outside the owner's own account; public page shows only owner-opted-in fields | Implemented |
| Admin session hijacking | `httponly`, `samesite=strict`, `secure` cookies; session regenerated on login | Implemented |
| Supply-chain compromise | Composer flagged and blocked installing Laravel 11 due to known CVEs; the project uses current stable (12) instead | Partial — no automated dependency-audit CI job yet |
| SSRF | No feature in this codebase performs server-initiated fetches of user-supplied URLs | Not applicable / avoided by design |
| Data exfiltration via bulk export | CSV export of QR batches requires `qr.export` permission and is audit-logged | Implemented for the one export feature that exists; report exports (section 18) not yet built |

## Not yet modeled (because not yet built)

Mobile app device compromise scenarios, NFC provisioning tampering, payment fraud — deferred until those features exist, to avoid threat-modeling code that isn't there.
