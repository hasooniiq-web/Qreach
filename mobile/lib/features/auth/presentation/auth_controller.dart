import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../../contacts/presentation/contact_controller.dart';
import '../../home/presentation/home_controller.dart';
import '../../notifications/presentation/notification_controller.dart';
import '../../profile/presentation/profile_controller.dart';
import '../../subscription/presentation/subscription_controller.dart';
import '../../vehicles/presentation/vehicle_controller.dart';
import '../data/user_model.dart';

sealed class AuthState {
  const AuthState();
}

class AuthUnknown extends AuthState {
  const AuthUnknown();
}

class AuthUnauthenticated extends AuthState {
  const AuthUnauthenticated();
}

/// A session exists (stored token) but the owner switched on Face ID/
/// fingerprint unlock and hasn't passed it yet this app open.
class AuthLocked extends AuthState {
  const AuthLocked();
}

class AuthAuthenticated extends AuthState {
  const AuthAuthenticated(this.user);
  final UserModel user;
}

/// Drives the router's redirect logic (see core/router/app_router.dart).
/// Starts as [AuthUnknown] until the stored-token check resolves, so the
/// splash screen never briefly flashes the wrong destination.
class AuthController extends Notifier<AuthState> {
  @override
  AuthState build() {
    _restore();
    return const AuthUnknown();
  }

  Future<void> _restore() async {
    final hasSession =
        await ref.read(authRepositoryProvider).hasStoredSession();
    if (!hasSession) {
      state = const AuthUnauthenticated();
      return;
    }

    final biometricEnabled =
        await ref.read(biometricServiceProvider).isEnabled();
    if (biometricEnabled) {
      state = const AuthLocked();
      return;
    }

    state = const AuthAuthenticated(_placeholderUser);
    ref.read(pushNotificationServiceProvider).registerForPushNotifications();
  }

  // A stored token implies a prior successful login, but we don't persist
  // the user object locally — treat as authenticated immediately and let
  // the vehicles screen's own API calls surface a 401 if the token is
  // actually stale, at which point [logout] is called.
  static const _placeholderUser = UserModel(
    id: '',
    phone: '',
    status: 'active',
    locale: 'ar',
    timezone: 'Asia/Baghdad',
  );

  /// Face ID/fingerprint (or the device passcode as a fallback) in front of
  /// the session that's already stored — not a second factor the server
  /// checks, purely a local lock screen. True only on an actual match.
  Future<bool> unlockWithBiometrics(String reason) async {
    final ok = await ref.read(biometricServiceProvider).authenticate(reason);
    if (ok) {
      state = const AuthAuthenticated(_placeholderUser);
      ref.read(pushNotificationServiceProvider).registerForPushNotifications();
    }
    return ok;
  }

  /// Step 1 of creating a new account: sends the verification OTP.
  Future<void> register({
    required String name,
    required String phone,
    required String password,
  }) =>
      ref.read(authRepositoryProvider).register(
            name: name,
            phone: phone,
            password: password,
          );

  /// Step 2: the OTP sent by [register].
  Future<void> verifyRegister(
      {required String phone, required String code}) async {
    final user = await ref.read(authRepositoryProvider).verifyRegister(
          phone: phone,
          code: code,
          deviceName: 'Mobile device',
        );
    state = AuthAuthenticated(user);
    ref.read(pushNotificationServiceProvider).registerForPushNotifications();
  }

  /// Phone + password. No OTP.
  Future<void> login({required String phone, required String password}) async {
    final user = await ref.read(authRepositoryProvider).login(
          phone: phone,
          password: password,
          deviceName: 'Mobile device',
        );
    state = AuthAuthenticated(user);
    ref.read(pushNotificationServiceProvider).registerForPushNotifications();
  }

  /// Sends the OTP used to set a first-ever password or reset a forgotten one.
  Future<void> requestPasswordOtp(String phone) =>
      ref.read(authRepositoryProvider).requestPasswordOtp(phone);

  /// Verifies that OTP, sets the new password, and logs in.
  Future<void> setPassword({
    required String phone,
    required String code,
    required String password,
  }) async {
    final user = await ref.read(authRepositoryProvider).setPassword(
          phone: phone,
          code: code,
          password: password,
          deviceName: 'Mobile device',
        );
    state = AuthAuthenticated(user);
    ref.read(pushNotificationServiceProvider).registerForPushNotifications();
  }

  Future<void> logout() async {
    await ref.read(authRepositoryProvider).logout();
    state = const AuthUnauthenticated();
    _clearPerAccountState();
  }

  Future<void> logoutAllDevices() async {
    await ref.read(authRepositoryProvider).logoutAllDevices();
    state = const AuthUnauthenticated();
    _clearPerAccountState();
  }

  /// The server ended this session (token revoked, expired, or the account was
  /// opened on another device): clear the stored token and return to login.
  /// Several in-flight requests can fail at once; only the first one acts.
  Future<void> sessionEnded({required bool replaced}) async {
    if (state is AuthUnauthenticated || state is AuthUnknown) return;
    await ref.read(secureStorageProvider).deleteToken();
    ref.read(sessionReplacedNoticeProvider.notifier).state = replaced;
    state = const AuthUnauthenticated();
    _clearPerAccountState();
  }

  /// Called by API-consuming providers when a request comes back
  /// unauthenticated (expired/revoked token) so the UI drops back to login
  /// immediately instead of showing a confusing error on every screen.
  void forceLogout() {
    state = const AuthUnauthenticated();
    _clearPerAccountState();
  }

  /// Every provider that caches data scoped to whoever was logged in.
  /// Without this, logging out and into a *different* account on the same
  /// app session kept showing the previous account's vehicles (reported
  /// bug) — VehicleListController isn't .autoDispose, so nothing ever
  /// re-fetched it until something happened to call refresh() by chance.
  /// Cleared here rather than relying on autoDispose timing, and covers
  /// the non-autoDispose ones too (vehicles/contacts/notifications).
  void _clearPerAccountState() {
    ref.invalidate(vehicleListControllerProvider);
    ref.invalidate(contactsProvider);
    ref.invalidate(notificationsProvider);
    ref.invalidate(subscriptionProvider);
    ref.invalidate(profileProvider);
    ref.invalidate(homeProvider);
  }
}

/// True after the server ended this session because the account signed in on
/// another device — shown once on the login screen.
final sessionReplacedNoticeProvider = StateProvider<bool>((ref) => false);

final authControllerProvider =
    NotifierProvider<AuthController, AuthState>(AuthController.new);

/// Whether this device even has usable biometrics right now — the settings
/// toggle only makes sense to show if so.
final biometricAvailableProvider = FutureProvider.autoDispose<bool>((ref) {
  return ref.watch(biometricServiceProvider).isAvailable();
});

/// Whether the owner switched biometric unlock on. Invalidated after toggling.
final biometricEnabledProvider = FutureProvider.autoDispose<bool>((ref) {
  return ref.watch(biometricServiceProvider).isEnabled();
});
