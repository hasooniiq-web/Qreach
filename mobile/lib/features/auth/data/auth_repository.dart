import 'package:dio/dio.dart';

import '../../../core/network/api_client.dart';
import '../../../core/storage/secure_storage_service.dart';
import 'user_model.dart';

class AuthRepository {
  AuthRepository(this._client, this._storage);

  final ApiClient _client;
  final SecureStorageService _storage;

  Future<void> requestOtp(String phone) async {
    await _client.dio.post('/auth/otp/request', data: {'phone': phone});
  }

  /// Returns the authenticated user and persists the bearer token to
  /// secure storage on success.
  Future<UserModel> verifyOtp({
    required String phone,
    required String code,
    String? deviceName,
    String platform = 'android',
  }) async {
    final response = await _client.dio.post<Map<String, dynamic>>(
      '/auth/otp/verify',
      data: {
        'phone': phone,
        'code': code,
        'device_name': deviceName,
        'platform': platform,
      },
    );

    final data = response.data!;
    final token = data['token'] as String;
    await _storage.saveToken(token);

    return UserModel.fromJson(data['user'] as Map<String, dynamic>);
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
