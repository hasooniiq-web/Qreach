# Release notes — October 2026 improvement pass

What changed, how it was verified, and what still needs credentials or manual
setup. Companion to `ARCHITECTURE_DECISIONS.md`.

## 1. Settings arrows / RTL

Flutter's `chevron_left/right` and `arrow_back/forward` glyphs mirror
themselves under RTL. The old code used `chevron_left`, which therefore pointed
the *wrong* way in both languages. Now: forward chevrons use `chevron_right`
(→ in LTR, ← in RTL), the auth back button uses `arrow_back`, and the two
non-mirroring glyphs that carry direction (`send`, `reply`) are flipped
explicitly in RTL. Unrelated icons are untouched.

## 2. Account-owner label removed from vehicles

The home carousel's vehicle card no longer shows "Owner: <name>". Nothing in the
database or API changed; the visibility toggle that controls whether the owner's
name appears to *visitors* on the public page is a different feature and stays.

## 3. iOS push notifications

Findings on the live server (`77.42.10.126`):

| Stage | State |
|---|---|
| Firebase service-account key on the server | present; OAuth + FCM call verified (a fake token returns `INVALID_ARGUMENT`, i.e. auth works) |
| `GoogleService-Info.plist`, bundle id `com.qreach` | present, matches |
| `aps-environment` entitlement, `remote-notification` background mode | present |
| iPhone → backend token registration | **never completed**: the only iOS device row on the server has `push_token = NULL` |

So the failure is on the phone: no APNs token was ever produced, which almost
always means the build was signed without the **Push Notifications** capability
on its App ID/provisioning profile (or an APNs key is not uploaded to Firebase).
That cannot be fixed from code, but the code was hardened:

- the token-refresh listener is registered *before* the APNs wait, so a token
  that arrives late still reaches the backend; the wait is 30 s instead of 10 s;
- iOS foreground presentation is enabled (otherwise nothing shows while the app
  is open);
- tapping a notification opens the conversation / notifications screen
  (`routeForPush`), for background and cold-start taps;
- the FCM payload now carries an explicit `apns` block (alert, priority 10,
  default sound);
- a new login re-registers the token (it was cached per process).

**Verification status: not verified end-to-end on a device.** What *was*
verified: backend credentials against FCM, unit tests for routing, the full
app/back-end suites. Required to finish (manual, Apple/Firebase side):

1. Apple Developer → Identifiers → `com.qreach` → enable **Push Notifications**.
2. Firebase console → Project settings → Cloud Messaging → *Apple app
   configuration* → upload an **APNs Authentication Key (.p8)** with its Key ID
   and Team ID.
3. Rebuild/sign with a profile that contains `aps-environment`; install on a
   real iPhone (push does not work in the simulator), log in, accept the
   permission prompt. Confirm `devices.push_token` is no longer NULL for that
   user, then send a visitor message.

## 4. Chat images

Visitor → owner photos already existed. Added owner → visitor: camera or library
(`image_picker`, bounded to 1600 px / JPEG q85, which also converts iPhone HEIC),
preview with a remove button, loading state on send, permission-denied and
type/size messages. The server re-validates (real image, `jpg/jpeg/png/webp`,
≤ 8 MB), re-encodes to JPEG (drops EXIF/GPS and any payload), stores on the
**private** disk and serves only through the authenticated, ownership-scoped
route. iOS `NSPhotoLibraryUsageDescription` added.

## 5. Conversations expire after 7 days

`ContactRequest::RETENTION_DAYS = 7`, measured on the **server** clock from the
last message (an active chat is not cut off mid-conversation). `contacts:purge`
now runs **hourly**, is chunked and idempotent, deletes rows first and files
second, and sweeps orphaned photos older than a day. Deletion is **permanent**
(hard delete): messages, photos, the copy of the text in the scan log and in the
owner's notification feed. Photos are never publicly addressable.

## 6. Visitor-page font

Public layout now loads the same family as the dashboard and app —
**Noto Sans Arabic** (Google Fonts weights 400–800 on the web; the bundled
`NotoSansArabic.ttf` in the app). It is set once as the Tailwind `--font-sans`
theme token, so every page inherits it.

## 7. Vehicle actions

The three-dot menu is replaced by two buttons at the bottom of the vehicle
screen: **Disable / Reactivate** (secondary) and **Delete** (red). Each opens a
confirmation dialog that states the consequence. Disabling sets
`vehicles.status = inactive`; the public page then shows the generic
"unavailable" page, records no scan and accepts no contact until reactivated.
Codes, details and past conversations are kept. Delete still goes through the
existing policy-checked `DELETE /vehicles/{id}` (soft delete).

## 8. One active session per account

Server-enforced. Every sign-in path (`register/verify`, `login`, `password/set`)
runs in one transaction holding a row lock on the user: previous Sanctum tokens
and device rows are revoked, then the new token is created — concurrent sign-ins
serialize and exactly one session survives. The previous device receives
`401` with code `SESSION_REPLACED` (remembered 30 days, cosmetic only) and the
app clears its token, returns to the welcome screen and says why (before this,
the app never reacted to a 401 at all). Tokens now also expire after
`SANCTUM_EXPIRATION` minutes (default **129600 = 90 days**, absolute); expired
rows are pruned daily. Admin-dashboard sessions are a separate guard and are not
affected.

## 9. Dynamic Island / Live Activities — recommendation: **do not implement**

A Live Activity is for a *short-lived, ongoing, glanceable* state the user is
actively waiting on (a delivery, a timer, a game score). QReach's real-time
events are already well served by ordinary push notifications:

- *A visitor wrote to you* — a one-off event; a push with a tap-through is the
  right pattern, and a Live Activity would sit on the lock screen showing a
  stranger's message to anyone who picks up the phone.
- *A payment in progress* — lasts seconds, and the app shows a status screen.
  Not worth a widget extension.
- *Subscription expiry* — a date, not an ongoing activity; a reminder push fits.

Costs of doing it anyway: a Widget Extension target, ActivityKit entitlements
(`NSSupportsLiveActivities`), push-to-start / activity-token plumbing in FCM and
the backend, iOS 16.1+ gating, and a lock-screen privacy surface. No scenario
here justifies that, and a decorative activity would be misleading. Revisit only
if a genuinely time-boxed flow appears (e.g. a live "someone is blocking your
car — responder on the way" session).

## 10. Electronic subscription payments

### What exists

Backend (`app/Services/Payments/*`, migration `2026_10_10_000000_electronic_payments`):

- `GatewayDriver` interface + `GatewayRegistry` — a provider is one class.
- `SandboxDriver` — a built-in test provider with its own checkout page and
  HMAC-signed notifications. **Refused when `APP_ENV=production`.** It moves no
  money; it exists so the whole flow can be exercised.
- `PaymentService` — the only code that changes a payment's state:
  - the amount is the plan's price read from the DB when the payment is created
    (the app never sends a price);
  - success is accepted only from a verified provider notification (or a status
    query) whose reference **and** amount/currency equal what the server created;
    a mismatch fails the payment, raises a `PAYMENT_ANOMALY` security event and
    blocks any later "success" for it;
  - row-locked transitions + a deduplicated event log (`payment_events`, unique
    `(gateway, event_key)`) → a repeated notification, or a second one for an
    already-paid payment, never activates or extends twice;
  - `idempotency_key` (unique) makes a retried "Pay" return the same payment;
  - pending payments are reconciled with the provider (when it can be asked) and
    closed after 60 minutes (`payments:maintain`, every 5 min);
  - refunds (full/partial) through the provider; a full refund ends the plan it bought.
- Customer API: `GET /payments/methods`, `POST /payments`, `GET /payments/{id}`,
  `GET /payments`; provider webhook `POST /api/v1/payments/webhook/{driver}`
  (unauthenticated, throttled, signature-verified); display-only return page
  `GET /pay/return/{id}`.
- Dashboard: **المدفوعات** (list, filters, detail with event log, refund form) and
  **بوابات الدفع** (enable/disable, test mode, credentials, webhook URL). Secrets
  are write-only: stored encrypted with `APP_KEY`, never rendered again, never in
  the audit log (only the *names* of changed fields). New permissions
  `payments.view`, `payments.refund`, `payment_gateways.view`, `payment_gateways.manage`
  (migration grants them to `super_admin`).

App: subscription → *Subscribe to this plan* → checkout (price, benefits,
methods) → in-app browser → status screen that polls the server and shows the
receipt (number, plan, amount, method, date, valid-until) or the failure.

### What it does NOT include — needs you

**No real provider is wired.** The project had none selected and this pass did
not invent one. To take real money:

1. Choose a provider that has a merchant programme for Iraq (candidates: ZainCash,
   FIB / First Iraqi Bank, Qi Card). Open a merchant account and obtain its
   credentials and API documentation.
2. Add `app/Services/Payments/<Provider>Driver.php` implementing `GatewayDriver`
   from *that provider's* documentation, and list it in `GatewayRegistry`. The
   dashboard, app, receipts, refunds and all the safety rules above then apply
   unchanged.
3. In the dashboard → بوابات الدفع, enter the credentials, set the provider's
   webhook URL shown there, switch off test mode and enable it.
4. Serve the API over **HTTPS** first. Providers require it, and the app
   currently allows cleartext only for the current server IP.

Until then customers see "no payment method is available". The sandbox is
**disabled by default**; do not enable it on a public server — anyone could
"pay" with it.

## Environment / config / migrations

| Item | Value |
|---|---|
| Migrations | `2026_10_10_000000_electronic_payments` (new tables `payment_gateways`, `payment_events`; new `payments` columns; permissions) |
| `.env` | `SANCTUM_EXPIRATION` (optional, minutes, default 129600); existing `FIREBASE_CREDENTIALS` |
| Scheduler | existing cron; new jobs `payments:maintain` (5 min), `contacts:purge` (hourly), `sanctum:prune-expired` (daily) |
| Web server | request bodies up to 10 MB: nginx `client_max_body_size 10m`; PHP `upload_max_filesize=10M`, `post_max_size=12M` (photos are ≤ 8 MB) |
| iOS | Push Notifications + NFC Tag Reading capabilities on the App ID; `NSPhotoLibraryUsageDescription`, `NFCReaderUsageDescription` (added); entitlements `aps-environment`, `com.apple.developer.nfc.readersession.formats` (present) |
| Build number | `0.1.0+12` |
