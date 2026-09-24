# API Reference

Base URL: `/api/v1`. All responses are JSON. Authenticated endpoints require `Authorization: Bearer {token}` (Sanctum personal access token).

## Error format

Every error response follows the same shape:

```json
{ "error": { "code": "TAG_ALREADY_ACTIVATED", "message": "هذا الرمز مُفعّل بالفعل ولا يمكن ربطه مرة أخرى." } }
```

Validation errors additionally include a `fields` object matching Laravel's standard validation error shape. Standard codes: `UNAUTHENTICATED` (401), `FORBIDDEN` (403), `NOT_FOUND` (404), `VALIDATION_FAILED` (422), `OTP_INVALID` / `OTP_COOLDOWN` / `TAG_NOT_FOUND` / `TAG_ALREADY_ACTIVATED` / `TAG_REVOKED` / `VEHICLE_NOT_OWNED` (422/429 as appropriate).

## Authentication

| Method | Path | Auth | Rate limit | Notes |
|---|---|---|---|---|
| POST | `/auth/otp/request` | none | 1/min, 5/hr per phone; 20/hr per IP | `{phone}` → sends a 6-digit OTP (dev: logged, not actually delivered — see `ARCHITECTURE_DECISIONS.md`) |
| POST | `/auth/otp/verify` | none | 10/min per phone; 30/hr per IP | `{phone, code, device_name?, platform?, push_token?, push_provider?}` → `{user, token}`. Registers the phone as a new account transparently if unseen (avoids account-enumeration). |
| POST | `/auth/logout` | Sanctum | — | Revokes the current token and its associated device. |
| POST | `/auth/logout-all` | Sanctum | — | Revokes every token/device for the account. |

## Vehicles

All routes below require Sanctum auth and enforce ownership via `VehiclePolicy` — a 403 is returned for any vehicle not owned by the caller, never a 404 that would leak existence.

| Method | Path | Notes |
|---|---|---|
| GET | `/vehicles` | Paginated, only the caller's own vehicles. |
| POST | `/vehicles` | `{plate_number, nickname?, make?, model?, year?, color?, vehicle_type?, description?, privacy_settings?}` |
| GET | `/vehicles/{uuid}` | |
| PUT/PATCH | `/vehicles/{uuid}` | |
| DELETE | `/vehicles/{uuid}` | Soft delete. |

## Tags

| Method | Path | Rate limit | Notes |
|---|---|---|---|
| POST | `/tags/activate` | 10/min per user, 60/hr per IP | `{token, vehicle_id}`. See `docs/QR_SECURITY.md` for the concurrency guarantees. |
| GET | `/tags/{token}` | — | Returns tag status for a tag on one of the caller's own vehicles. |

## Public (no authentication, no `/api` prefix — served from web routes)

| Method | Path | Rate limit |
|---|---|---|
| GET | `/t/{token}` | 30/min per IP |
| POST | `/t/{token}/vehicle-block` | 6/min per IP, 3/min per token |
| POST | `/t/{token}/lights-on` | 6/min per IP, 3/min per token |
| POST | `/t/{token}/message` | 6/min per IP, 3/min per token — `{message}` (plain text only, max 300 chars) |
| POST | `/t/{token}/emergency` | 3/hr per IP, 3/hr per token — `{confirmed: true, message?}` |
| POST | `/t/{token}/contact` | 5/hr per IP, 3/hr per token — `{message, visitor_name?, visitor_phone?}` |

## Not yet built

- Profile / contact-methods / emergency-contacts endpoints (schema exists, no controllers yet)
- Admin API (the dashboard is server-rendered Blade, not a separate JSON API)
- NFC provisioning endpoints
- Reports/export endpoints
- Device push-token update endpoint (registration happens only at OTP-verify time currently)

## Versioning

Routes are prefixed `/api/v1/` per section 32 of the spec. There is only one version currently; no deprecation policy has been needed yet.
