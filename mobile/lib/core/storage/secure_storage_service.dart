import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Wraps flutter_secure_storage (Android Keystore / iOS Keychain backed) —
/// the Sanctum bearer token must never be stored in SharedPreferences/plain
/// files (section 26, OWASP MASVS storage requirements).
class SecureStorageService {
  SecureStorageService()
      : _storage = const FlutterSecureStorage(
          aOptions: AndroidOptions(encryptedSharedPreferences: true),
          iOptions:
              IOSOptions(accessibility: KeychainAccessibility.first_unlock),
        );

  final FlutterSecureStorage _storage;

  static const _tokenKey = 'auth_token';
  static const _localeKey = 'locale';

  Future<void> saveToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  Future<String?> readToken() => _storage.read(key: _tokenKey);

  Future<void> deleteToken() => _storage.delete(key: _tokenKey);

  Future<void> saveLocale(String localeCode) =>
      _storage.write(key: _localeKey, value: localeCode);

  Future<String?> readLocale() => _storage.read(key: _localeKey);
}
