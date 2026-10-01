import 'package:local_auth/local_auth.dart';

import '../storage/secure_storage_service.dart';

/// Face ID / fingerprint as an opt-in alternative to retyping the password
/// on each app open. Purely local: the server has no concept of this —
/// it's a lock screen in front of the already-stored session token, not a
/// second authentication factor it checks.
class BiometricService {
  BiometricService(this._storage) : _auth = LocalAuthentication();

  final SecureStorageService _storage;
  final LocalAuthentication _auth;

  static const _enabledKey = 'biometric_unlock_enabled';

  /// Whether this device even has usable biometrics (enrolled fingerprint/
  /// face) right now — the settings toggle only makes sense to show if so.
  Future<bool> isAvailable() async {
    try {
      final supported = await _auth.isDeviceSupported();
      final canCheck = await _auth.canCheckBiometrics;
      return supported && canCheck;
    } catch (_) {
      return false;
    }
  }

  Future<bool> isEnabled() async {
    final value = await _storage.readValue(_enabledKey);
    return value == 'true';
  }

  Future<void> setEnabled(bool enabled) =>
      _storage.writeValue(_enabledKey, enabled.toString());

  /// Prompts for Face ID / fingerprint (or the device passcode as a
  /// fallback). True only on an actual successful match.
  Future<bool> authenticate(String reason) async {
    try {
      return await _auth.authenticate(
        localizedReason: reason,
        options: const AuthenticationOptions(biometricOnly: false),
      );
    } catch (_) {
      return false;
    }
  }
}
