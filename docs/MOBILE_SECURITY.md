# Mobile Security

## Current status: no mobile app exists yet

This document records the security requirements the (not-yet-built) Flutter app must satisfy, per section 26 of the spec (OWASP MASVS), so they aren't lost between now and when mobile work starts.

## Required controls, by MASVS area

| Area | Requirement | Notes for implementation |
|---|---|---|
| Storage | Sanctum tokens must go in Android Keystore-backed / iOS Keychain-backed secure storage (`flutter_secure_storage` or equivalent), never `SharedPreferences`/plain files | The backend already issues a plain bearer token (`createToken()`); the app is responsible for storing it securely — the backend can't enforce this from the server side |
| Network | HTTPS only, no cleartext traffic permitted in the app's network security config (Android) / ATS (iOS) | Backend already redirects to HTTPS in production (`URL::forceScheme('https')`) but the mobile app must also refuse to run over plaintext |
| Authentication | OTP flow already exists server-side (`/api/v1/auth/otp/*`); app should also support device-level biometric/PIN gating before exposing a stored session | Not a backend concern, but worth noting the API already returns a `device_name`/`platform` field the app should populate accurately for the admin's device-inventory view |
| Code quality | No secrets embedded in the app bundle | The backend has no API keys the mobile app needs to embed — Sanctum auth doesn't require a client secret, which avoids this risk category entirely for the current API surface |
| Resilience | Certificate pinning — evaluate only once a real production TLS cert/rotation strategy exists | Deferred per spec section 26 ("evaluate only when justified") |
| Privacy | Push token registration already has a backend endpoint shape (`push_token`/`push_provider` fields accepted at OTP-verify time) but no dedicated update-token endpoint exists yet for token refresh after initial login | **Gap**: add `PATCH /api/v1/devices/{id}` or similar before mobile work starts, so a refreshed FCM/APNs token isn't stuck to the original login |

## What the backend already provides for the mobile app to use

- Phone+OTP auth (`docs/API.md`)
- Vehicle CRUD, tag activation
- Device registration at login (`devices` table, linked to the Sanctum token for later revocation)
- `logout` / `logout-all` for session/device management screens

## What's missing before mobile work can start end-to-end

- Profile/contact-methods/emergency-contacts API endpoints (schema exists, no controllers)
- A device push-token refresh endpoint
- Real FCM/APNs server-side delivery (currently notifications only reach the database)
