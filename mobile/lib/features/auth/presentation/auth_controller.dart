import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
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
    state = hasSession
        ? const AuthAuthenticated(_placeholderUser)
        : const AuthUnauthenticated();

    if (hasSession) {
      ref.read(pushNotificationServiceProvider).registerForPushNotifications();
    }
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

  Future<void> requestOtp(String phone) =>
      ref.read(authRepositoryProvider).requestOtp(phone);

  Future<void> verifyOtp({required String phone, required String code}) async {
    final user = await ref.read(authRepositoryProvider).verifyOtp(
          phone: phone,
          code: code,
          deviceName: 'Mobile device',
        );
    state = AuthAuthenticated(user);
    ref.read(pushNotificationServiceProvider).registerForPushNotifications();
  }

  Future<void> logout() async {
    await ref.read(authRepositoryProvider).logout();
    state = const AuthUnauthenticated();
  }

  Future<void> logoutAllDevices() async {
    await ref.read(authRepositoryProvider).logoutAllDevices();
    state = const AuthUnauthenticated();
  }

  /// Called by API-consuming providers when a request comes back
  /// unauthenticated (expired/revoked token) so the UI drops back to login
  /// immediately instead of showing a confusing error on every screen.
  void forceLogout() {
    state = const AuthUnauthenticated();
  }
}

final authControllerProvider =
    NotifierProvider<AuthController, AuthState>(AuthController.new);
