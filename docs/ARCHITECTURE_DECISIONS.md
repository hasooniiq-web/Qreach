# Architecture Decisions

Ambiguities in the spec that required a judgment call, per section 59's instruction to "choose the safest production-grade interpretation... document the decision... do not invent business rules that materially change the product."

## Laravel 12 instead of an unpinned/older version

The spec says "Laravel" without a version. Composer's own security-advisory audit refused to install Laravel 11.x (`^11.31`) due to known CVEs affecting every 11.x release currently on Packagist. Current stable (12.x) was used instead — no functional difference for this project, and it avoids shipping known-vulnerable framework code from day one.

## App name "Qreach" vs. product name "SmartTag"

The working directory and the user's own instruction mid-session established "Qreach" as the umbrella application/company name, with "SmartTag" remaining the vehicle-tag product name used throughout the spec's feature descriptions. Both appear together in the UI (`Qreach` in bold, `SmartTag` as a lighter secondary label) rather than picking one and discarding the other.

## SMS delivery is a dev-only stub

No SMS gateway/provider is named anywhere in the spec. Rather than guess a provider (and its pricing, account setup, and API shape), `App\Contracts\SmsGatewayContract` defines the interface the rest of the app depends on, and `LogSmsGateway` (log-only, refuses to run when `APP_ENV=production`) is the only implementation. **A real provider must be wired in before this ships** — this is the single most important "not actually done" item for a production launch, since without it customers cannot receive OTP codes at all.

## Payment/subscription schema without payment logic

Same reasoning as SMS: no PSP, currency, or pricing plan is specified. The schema (`subscription_plans`, `subscriptions`, `payments`) is built to be idempotency-safe for whenever a gateway is chosen, but no checkout flow, webhook handler, or plan-enforcement logic exists yet.

## RBAC is hand-rolled, not a package

`spatie/laravel-permission` (or similar) is the common choice, but it's built around a generic "any model can have roles" abstraction. This system has exactly one privileged actor type (`Admin`), so a small dedicated `roles`/`permissions`/`role_permission`/`admin_role` schema was judged clearer and easier to audit than pulling in a general-purpose package's conventions.

## Admin role/permission assignment, not a role *builder*

The dashboard lets an admin assign an existing (seeded) role to another admin, but doesn't offer a UI to invent new roles or edit which permissions belong to a role. The spec's own example roles (Super Admin, Security Admin, Operations Admin, Support Admin, Reports Analyst) are treated as the fixed set for this pass; a full permission-matrix editor was judged lower-value than finishing the rest of the platform. This can be added later without a schema change — `roles`/`permissions` already support arbitrary new rows.

## Public page shows vehicle fields via an explicit allow-list, defaulting to visible

`Vehicle::publicAttributes()` defaults each field (nickname, make, model, color, vehicle_type) to visible unless the owner's `privacy_settings` JSON explicitly sets it to `false`. This was chosen over a default-hidden approach because the product's stated purpose (a stranger being able to identify "yes, this is the car I'm looking at") requires *some* identifying info to be visible by default for the product to function at all — the plate number itself is excluded from the public-eligible field list entirely, since it's already physically visible on the vehicle and re-displaying it adds no value while marginally aiding data aggregation.

## Analytics aggregation job is scaffolded, not scheduled

`analytics_daily_stats` exists as a table but nothing currently writes to it — no `php artisan schedule` job aggregates `scans`/`audit_logs` into it yet. Building the aggregation job without also building the reporting UI that would consume it was judged not worth the partial-completion risk this session; both are deferred together.

## Mobile: Firebase/FCM — client-side wired once a real Firebase project existed; server-side sending still isn't

The previous decision here deferred `firebase_messaging`/`firebase_core` entirely because there was no real Firebase project to configure them against — adding the packages without one would compile but crash or silently no-op at runtime. That precondition changed once a real `google-services.json` (project `qreach-3d577`, package `com.Qreach`) was provided. The Android app's `applicationId`/`namespace` were changed from `com.qreach.qreach_smarttag` to `com.Qreach` to match it exactly (the Google Services Gradle plugin fails the build otherwise) — verified with a real `flutter build apk --release`, not just `flutter analyze`.

What's now real: the app requests notification permission, retrieves an FCM token, and registers/refreshes it against a new `PUT /api/v1/devices/push-token` endpoint (added because none existed — `Device` rows were previously only created once, at OTP-verify time, with no way to update a rotated token later).

What's still a stub: **no backend code actually sends a push through FCM.** `google-services.json` is a client-side config; dispatching a real push from Laravel needs a separate Firebase *service account* credential (server-side, not yet provided) plus an FCM-sending implementation (e.g. `kreait/firebase-php`) wired into the existing notification classes (`TagActionNotification`, `ContactRequestNotification`), which today only use the `database` channel. Building that sending path without credentials to actually test it against would repeat the same "looks done but isn't" risk this decision originally avoided — so it's called out here explicitly rather than half-built.

## Mobile: Material Icons instead of a third-party icon package

`phosphor_flutter` was the original choice (matches the admin dashboard's outline-icon aesthetic) but doesn't compile against the Flutter/Dart SDK version available in this environment — `IconData` became a `final class`, breaking the package's `PhosphorIconData extends IconData` subclass. This was only discovered via an actual `flutter build apk` (static analysis passed cleanly despite the incompatibility). Rather than pin to an older Flutter/Dart version to keep a cosmetic icon package, the app uses Flutter's bundled Material Icons, which carries no external SDK-compatibility risk.

## Mobile: IBM Plex Sans Arabic via `google_fonts`, not bundled assets

`google_fonts` fetches and caches the font at runtime rather than shipping the `.ttf` files in the app bundle. This is the faster path to a correct, on-brand result without needing to source and vendor font files by hand, at the cost of a first-run network dependency for text rendering. A fully offline-first production build should replace this with bundled font assets before shipping — noted as a specific, actionable gap in `mobile/README.md` rather than left implicit.


## Plans: Basic (free default) and Pro, enforced server-side

Basic lets visitors only message the owner. Pro also lets the owner show a phone number, Instagram handle and extra info on the public scan page. `subscription_plans.features` (JSON) says what a plan allows; `App\Services\PlanEntitlements` is the only place that decides, and both the API (`PUT /profile/contact` refuses `visible:true` for Basic owners) and the scan page (`PublicContacts` re-checks the plan on every render) go through it — the app and dashboard only display the result. A customer with no active paid subscription is on Basic, which has no subscription row; only explicit grants create rows. An "active" row past its end date is treated as expired everywhere (`Subscription::isCurrentlyActive`).

**No payment gateway exists**, so upgrading is a request (`plan_upgrade_requests`) that an admin approves after taking payment out of band; approving/granting/canceling is manual, permission-gated (`subscriptions.view` / `subscriptions.manage`) and audit-logged. The dashboard's "value" KPI is the listed price of active subscriptions (expected value), not money received, and is labeled that way. **The Pro price (25,000 IQD/year) is a placeholder** — editable per plan in the dashboard; Basic is forced free and active.


## One code per vehicle
A vehicle has at most one assigned code. `TagLifecycleService::activate` locks the vehicle row and refuses a second (`VEHICLE_ALREADY_HAS_TAG`); to change it the owner removes the code (`DELETE /vehicles/{id}/tag`, which revokes it) and links a new one.

## Basic / Plus and visitor visibility
Visitors see nothing about the owner by default — not the name, phone, emergency contact or any contact method. The owner switches items on individually. Basic may show the name, the phone number and one emergency contact; Plus additionally lets the owner add contact platforms (Instagram, WhatsApp, Snapchat, TikTok, X, Telegram, Facebook, LinkedIn, YouTube, website, email, other). What a plan allows lives in `subscription_plans.features` (`extra_contact_methods`, `emergency_contact`, `public_contacts`); `App\Services\PlanEntitlements` decides, the API refuses with `403 PLAN_REQUIRED` (`POST /profile/methods`, `visible:true` on a disallowed item) and `PublicContacts` re-checks on every scan-page render. Plus is activated by an admin (dashboard) or via the upgrade-request flow; there is no payment gateway yet.

## Near-real-time updates
Until server-side FCM is configured, freshness is polling: the app re-reads notifications, conversations and the home feed every 12 s while in the foreground (and on resume); the visitor's conversation page polls its JSON endpoint every 6 s; the dashboard sidebar badges poll `admin/poll` every 15 s.
