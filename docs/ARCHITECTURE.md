# Architecture

## System components

```
┌─────────────────┐     ┌──────────────────────────────────────────┐
│  Flutter app     │────▶│                                          │
│  (mobile/)       │     │   Laravel 12 application (backend/)      │
└─────────────────┘     │                                          │
                         │   ┌────────────┐  ┌───────────────────┐ │
┌─────────────────┐     │   │ REST API   │  │ Admin dashboard    │ │
│ Public visitor   │────▶│   │ /api/v1/*  │  │ /admin/* (Blade)   │ │
│ (scans QR/NFC)   │     │   │ Sanctum    │  │ session + RBAC     │ │
└─────────────────┘     │   └────────────┘  └───────────────────┘ │
                         │   ┌────────────┐                        │
                         │   │ Public scan│                        │
                         │   │ /t/{token} │                        │
                         │   └────────────┘                        │
                         └──────────────┬───────────────────────────┘
                                        │
                    ┌───────────────────┼───────────────────┐
                    ▼                   ▼                   ▼
             ┌─────────────┐    ┌─────────────┐    ┌────────────────┐
             │ PostgreSQL  │    │ Queue worker│    │ Local disk      │
             │ (all data)  │    │ (notifs)    │    │ (QR PNG/SVG)    │
             └─────────────┘    └─────────────┘    └────────────────┘
```

## Request flow: QR activation (the core security-critical path)

1. Customer authenticates via phone + OTP → Sanctum bearer token.
2. Customer creates a vehicle (`POST /api/v1/vehicles`), ownership recorded server-side.
3. Customer scans a physical QR tag, extracts the `public_token` from the URL, and calls `POST /api/v1/tags/activate` with `{token, vehicle_id}`.
4. `TagController::activate` authorizes the vehicle belongs to the caller (`VehiclePolicy`), then delegates to `TagLifecycleService::activate`.
5. Inside a DB transaction: the tag row is locked (`lockForUpdate`), its status is checked, and the actual state transition is a **conditional UPDATE** (`WHERE status IN ('available','generated')`) whose affected-row count is the real correctness guarantee — not the lock alone. This is verified under genuine concurrent load (10 simultaneous requests → exactly 1 succeeds); see `docs/QR_SECURITY.md`.
6. A `tag_assignments` row records the event; an `audit_logs` row records the action.

## Request flow: public scan

1. Visitor scans the physical tag → browser opens `https://.../t/{public_token}`.
2. `ScanController::show` looks up the tag by token. Any failure mode (unknown token, revoked, wrong status, no vehicle) renders the **same** generic "invalid" page — there is no oracle for guessing which tokens exist.
3. The page shows only the vehicle fields the owner has opted into (`Vehicle::publicAttributes()`), never the owner's private contact info.
4. An action (message, emergency, contact request, etc.) is validated server-side (`PlainTextOnly` rule blocks HTML/script content), rate-limited per-IP and per-token, recorded in `scans` (and `contact_requests` for the contact-owner flow), and queues a notification to the owner.

## Technology stack

| Layer | Choice | Why |
|---|---|---|
| Backend framework | Laravel 12 | Spec asked for "Laravel"; 11 is now flagged by Composer's security-advisory audit, so 12 (current stable) was used instead — see `ARCHITECTURE_DECISIONS.md`. |
| Database | PostgreSQL 18 | Required by spec. |
| API auth | Laravel Sanctum | Bearer tokens for mobile, no session/cookie complexity needed for a pure API client. |
| Admin auth | Session guard, separate from Sanctum | Admins and customers never share a table, guard, or session (section 20 of the spec). |
| Password hashing | Argon2id | Verified available on this PHP build; stronger default than bcrypt. |
| QR generation | `endroid/qr-code` | Mature, maintained, supports error-correction level H needed for print durability. |
| Admin dashboard UI | Blade + Tailwind v4 (Vite) | Server-rendered, no SPA complexity needed for an internal tool; matches "desktop-first admin" requirement while still being responsive. |
| MFA | `pragmarx/google2fa-laravel` | TOTP for admin accounts. |
| Mobile framework | Flutter 3.47 + Riverpod + go_router + Dio | Required by spec; state management/routing/networking choices are the current mainstream conventions for a maintainable app of this size. |

## What's real vs. scaffolded

**Backend**: customer auth, vehicle management, the QR engine, the public scan experience, and the core admin dashboard are implemented and covered by automated tests. Push notification delivery, payment processing, and NFC provisioning are schema-ready but not built.

**Mobile** (`mobile/`): login (phone+OTP), vehicle CRUD, and QR-scan-to-activate are implemented. `flutter analyze` is clean, `flutter build apk --debug` produces a real APK, `flutter test` passes, and the app was manually exercised via a browser-based web build (catching and fixing a real layout overflow bug in the process). It has **not** been run on an actual Android/iOS device or emulator — none was available in this environment. Push notifications, profile/emergency-contact screens, and biometric re-auth are not built.

Full breakdown in the project's final report / commit history.
