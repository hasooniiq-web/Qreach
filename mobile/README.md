# Qreach SmartTag — Mobile App

Flutter customer app: phone+OTP login, vehicle management, and QR tag activation via the camera.

## Status

Genuinely builds and runs — not just written, actually verified:
- `flutter analyze`: clean (2 harmless style infos on idiomatic guard clauses)
- `flutter build apk --debug`: succeeds, produces a real installable APK
- `flutter test`: passes
- Manually verified rendering (Arabic RTL, IBM Plex Sans Arabic, brand colors) via `flutter run -d web-server` and the browser — including catching and fixing a real layout bug (a `Spacer()`-based column that overflowed on constrained viewport heights) that `flutter analyze` did not catch

**Not verified**: on an actual Android/iOS device or emulator (none was available in this environment — no AVD image, no physical device). The debug APK exists at `build/app/outputs/flutter-apk/app-debug.apk` after running the build command below; install it on a real device to complete that verification.

## Setup

```bash
flutter pub get
flutter gen-l10n   # regenerates lib/l10n/app_localizations*.dart from the .arb files
```

To point at a local backend instead of the production URL baked into `core/network/api_client.dart`:

```bash
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1   # 10.0.2.2 = host machine, from an Android emulator
```

## Architecture

Feature-first, Riverpod for state, GoRouter for navigation:

```
lib/
  core/            Theme, networking (Dio + structured error handling), secure storage, router
  features/
    auth/          Phone+OTP login
    vehicles/      CRUD + tag list
    tags/          QR scanning + activation (mobile_scanner)
    profile/       Logout / logout-all-devices
  shared/widgets/  Small reusable pieces (empty state, error banner)
  l10n/            Arabic (default) + English ARB files
```

See `docs/MOBILE_SECURITY.md` and `docs/ARCHITECTURE_DECISIONS.md` at the repo root for the security requirements this app must satisfy and the tradeoffs made building it (e.g. why Firebase/FCM isn't wired in yet, why Material Icons were used instead of a third-party icon package).

## Known gaps

- No push notification delivery (FCM/APNs) — device registration happens with `push_token: null`
- No profile/contact-methods/emergency-contacts screens (backend API for these doesn't exist yet either)
- No certificate pinning (deferred per spec — evaluate once a real production TLS setup exists)
- Not tested on a real device/emulator (see Status above)
- IBM Plex Sans Arabic is loaded via `google_fonts` at runtime (fetched once, then cached) rather than bundled as local assets — fine for now, but a fully offline-first build should bundle the `.ttf` files instead

## A real bug found and fixed during development

A Kotlin/Gradle incremental-compiler bug ("this and base files have different roots") blocked every Android build until `kotlin.incremental=false` was added to `android/gradle.properties` — a known issue when the Flutter project and the Gradle/pub caches live on different drive letters on Windows. Also: `phosphor_flutter` (originally chosen for icons) doesn't compile against this Flutter/Dart SDK version (`IconData` is now a `final class`, breaking the package's subclass) — this was caught by an actual `flutter build apk`, not by `flutter analyze`, which passed cleanly despite the incompatibility. Replaced with Flutter's bundled Material Icons rather than gambling on another third-party package's SDK compatibility.
