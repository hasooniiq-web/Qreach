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
