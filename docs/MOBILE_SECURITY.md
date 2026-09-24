# Mobile Security

## Current status: app exists (`mobile/`), builds and passes analysis, not yet run on a real device or emulator

## Controls, by MASVS area, and their actual implementation status

| Area | Requirement | Status |
|---|---|---|
| Storage | Sanctum token in Android Keystore-backed / iOS Keychain-backed secure storage, never `SharedPreferences`/plain files | **Implemented** — `SecureStorageService` wraps `flutter_secure_storage` with `encryptedSharedPreferences: true` (Android) and `first_unlock` Keychain accessibility (iOS) |
| Network | HTTPS only, no cleartext traffic | **Partially implemented** — the API client defaults to an HTTPS base URL and is compile-time overridable via `--dart-define=API_BASE_URL`; the Android/iOS network-security-config/ATS *enforcement* that actively blocks cleartext hasn't been added to the platform manifests yet (the debug build still allows the `http://10.0.2.2` local-dev override to work, which is correct for debug but must be locked down for release builds) |
| Authentication | Phone+OTP login; device-level biometric/PIN gating before exposing a stored session | OTP flow **implemented** end-to-end (request/verify screens, resend cooldown, structured error handling for `TAG_*`/`OTP_*` codes). Biometric/PIN re-gating of an already-stored session is **not implemented** — a stored token currently grants access on app open with no additional local gate |
| Code quality | No secrets embedded in the app bundle | **Satisfied by design** — Sanctum auth needs no client secret, so there was nothing to embed |
| Resilience | Certificate pinning | **Deliberately deferred** per spec section 26 ("evaluate only when justified") — no production TLS/rotation strategy exists yet to pin against |
| Push notifications | Device registration | **Partially implemented** — the app sends `device_name`/`platform` at OTP-verify time; `push_token` is always `null` since no FCM/APNs SDK is integrated (see `ARCHITECTURE_DECISIONS.md` for why Firebase was deliberately left out rather than half-wired) |

## Real issues found and fixed while building this app

- **`phosphor_flutter` doesn't compile against the current Flutter/Dart SDK** (`IconData` is now a `final class`, and the package's `PhosphorIconData extends IconData` breaks). This was invisible to `flutter analyze` — it only surfaced during an actual `flutter build apk`. Replaced with Flutter's bundled Material Icons, which carries no third-party SDK-compatibility risk.
- **A `Spacer()`-based `Column` overflowed on short viewports** (a real `RenderFlex overflowed` runtime error, caught by actually running the app in a browser, not by static analysis). Fixed with a `LayoutBuilder` + `SingleChildScrollView` pattern that's also more correct for when the on-screen keyboard opens on a real device.
- **Kotlin incremental compilation fails when the Flutter project and Gradle/pub caches are on different Windows drive letters** (`this and base files have different roots`). Worked around with `kotlin.incremental=false` in `android/gradle.properties` — a toolchain limitation of this specific dev machine, not an app defect, but documented in case it recurs elsewhere.

## What's still missing before this is production-ready

- Real FCM/APNs integration (see `ARCHITECTURE_DECISIONS.md`)
- Network-security-config (Android) / ATS exceptions review (iOS) for release builds specifically
- Biometric/PIN re-authentication for an already-stored session
- Testing on an actual Android/iOS device or emulator — this environment had no AVD image and no connected device; verification here was `flutter analyze` (clean), `flutter build apk --debug` (succeeds, real APK produced), `flutter test` (passes), and manual interaction via `flutter run -d web-server` in a browser (confirmed correct RTL/Arabic rendering and caught the layout bug above) — real, but not a substitute for on-device testing
- Profile/contact-methods/emergency-contacts screens (backend API for these doesn't exist yet either — see `docs/API.md`)
