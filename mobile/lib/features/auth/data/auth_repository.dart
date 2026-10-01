import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../../../core/network/api_client.dart';
import '../../../core/storage/secure_storage_service.dart';
import 'user_model.dart';

class AuthRepository {
  AuthRepository(this._client, this._storage);

  final ApiClient _client;
  final SecureStorageService _storage;

  String get _platform => switch (defaultTargetPlatform) {
        TargetPlatform.iOS => 'ios',
        TargetPlatform.android => 'android',
        _ => 'web',
      };

  /// Step 1 of creating a new account: name + phone + password. Sends a
  /// verification OTP; the account isn't usable until [verifyRegister]
  /// succeeds.
  Future<void> register({
    required String name,
    required String phone,
    required String password,
  }) async {
    await _client.dio.post('/auth/register', data: {
      'name': name,
      'phone': phone,
      'password': password,
    });
  }

  /// Step 2: the OTP sent in [register]. Returns the now-active account and
  /// persists the bearer token, same as [login].
  Future<UserModel> verifyRegister({
    required String phone,
    required String code,
    String? deviceName,
  }) =>
      _postAndSaveSession('/auth/register/verify', {
        'phone': phone,
        'code': code,
        'device_name': deviceName,
        'platform': _platform,
      });

  /// Phone + password. No OTP — that only happens once, during registration
  /// or a password (re)set.
  Future<UserModel> login({
    required String phone,
    required String password,
    String? deviceName,
  }) =>
      _postAndSaveSession('/auth/login', {
        'phone': phone,
        'password': password,
        'device_name': deviceName,
        'platform': _platform,
      });

  /// Sends the OTP used to set a first-ever password (an account that
  /// pre-dates password login) or to reset a forgotten one.
  Future<void> requestPasswordOtp(String phone) async {
    await _client.dio
        .post('/auth/password/request-otp', data: {'phone': phone});
  }

  /// Verifies that OTP and sets the new password in one step, then logs in.
  Future<UserModel> setPassword({
    required String phone,
    required String code,
    required String password,
    String? deviceName,
  }) =>
      _postAndSaveSession('/auth/password/set', {
        'phone': phone,
        'code': code,
        'password': password,
        'device_name': deviceName,
        'platform': _platform,
      });

  Future<UserModel> _postAndSaveSession(
      String path, Map<String, dynamic> data) async {
    final response =
        await _client.dio.post<Map<String, dynamic>>(path, data: data);

    final body = response.data!;
    final token = body['token'] as String;
    await _storage.saveToken(token);

    return UserModel.fromJson(body['user'] as Map<String, dynamic>);
  }

  Future<void> logout() async {
    try {
      await _client.dio.post('/auth/logout');
    } on DioException {
      // Even if the network call fails, still clear the local token below —
      // a user tapping "log out" expects to be logged out locally regardless.
    } finally {
      await _storage.deleteToken();
    }
  }

  Future<void> logoutAllDevices() async {
    try {
      await _client.dio.post('/auth/logout-all');
    } finally {
      await _storage.deleteToken();
    }
  }

  Future<bool> hasStoredSession() async {
    final token = await _storage.readToken();
    return token != null && token.isNotEmpty;
  }
}
