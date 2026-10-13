# Sign-in lockout, one-time-code caps and flood protection

Added 2026-10-14. Everything below is enforced on the server; the apps only explain it.

## 1. Escalating lockout (`App\Services\LoginLockout`)

Three wrong secrets **in a row** for one account lock that account:

| Lockout # | Duration |
|-----------|----------|
| 1st       | 1 hour   |
| 2nd       | 10 hours |
| 3rd       | 24 hours |
| 4th       | 3 days   |
| 5th and later | 7 days |

* Applies to: customer password sign-in, customer one-time-code entry (registration / password reset),
  dashboard password, dashboard authenticator code. Scopes are separate (`customer`, `customer_otp`,
  `admin`, `admin_totp`), stored in `auth_lockouts`, so a cache flush or restart does not unlock anyone.
* The lock is checked **before** the secret is compared: the right password is refused while locked.
* Keyed by account (phone / email), **never by IP** — mobile carriers put many customers behind one address.
  An unknown phone or email is counted and answered exactly like a real one (no account enumeration).
* A correct secret clears the history. 24 h without a wrong try starts the count over; 30 quiet days reset
  the level. A password reset by one-time code proves the phone and lifts the password lock.
* API answer: `429 AUTH_LOCKED` with `retry_after` (seconds), `locked_until`, and a `Retry-After` header.
  A wrong password answers `422 INVALID_CREDENTIALS` with `attempts_left`.
* Tunables: `config/security.php` → `lockout`.

Trade-off to know: anyone who knows a phone number can lock that customer out by typing wrong passwords.
That is the price of a per-account lock; the customer can still regain access with a one-time code, which
lifts the password lock.

## 2. One-time-code sending caps (`OtpService::request`)

Counted from the `otp_codes` table, on the *normalized* phone, so `0770…`, `+964770…` and `770…` are one number.

| Scope | Cap |
|-------|-----|
| Per phone | 5 / hour, 10 / day |
| Per address | 15 / hour, 40 / day |
| Whole system | 3,000 / day (protects the SMS bill) |
| Between two sends to one number | 60 s, then 2 min, 4 min, 8 min … up to 15 min |

Over a cap: `429 OTP_LIMIT` with `retry_after`. Over the cooldown: `429 OTP_COOLDOWN`.
Tunables: `config/security.php` → `otp` (or `OTP_*` env values). Hitting a cap is logged as a security event.

## 3. Flood protection

**In the application**

* `ThrottleByAddress` (global middleware, runs before the session and before sign-in): 600 requests / minute per
  address for the web and 600 / minute for the API (`WEB_RATE_PER_MINUTE`, `API_RATE_PER_MINUTE`). Unauthenticated
  requests are counted too — Laravel's own `throttle:api` runs *after* authentication and cannot see them.
* Per-route limits (sign-in, OTP, register, scan, emergency, contact form, reseller look-up) are unchanged and stricter.
* Exports: 6 / minute per admin, **two at a time** system-wide (cache locks), row caps (20,000 Excel / 1,000 PDF).
* The reseller look-up and the contact form have their own limits and lockout.

**On the server** (`deploy/harden-server.sh`, applied only when run)

* nginx: 20 req/s per address (burst 80), 40 connections per address, 1 req/s on sign-in / OTP / register
  (burst 8), 30 req/min on the public forms, slow-client timeouts, no version banner, 60 s PHP timeout.
* php-fpm: `request_terminate_timeout = 60s`, `pm.max_requests = 500`.
* ufw: `limit 22/tcp` (SSH brute force).
* fail2ban: `sshd` and `nginx-limit-req` jails (1 hour bans).

**What this does not do.** A single small server cannot absorb a *volumetric* attack (a large botnet
saturating the network link). The real answer to that is a proxy in front (Cloudflare's free plan hides the
server address and absorbs it). When that is added, set `TRUSTED_PROXIES` so the real visitor address is used
by the limits above.
