import 'package:dio/dio.dart';

import '../storage/secure_storage_service.dart';
import 'api_exception.dart';

/// Base URL is compile-time configurable so a debug build can point at a
/// local backend without editing source:
///   flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
const _defaultBaseUrl = 'http://77.42.10.126/api/v1';
const String apiBaseUrl =
    String.fromEnvironment('API_BASE_URL', defaultValue: _defaultBaseUrl);

/// Thin wrapper around Dio: injects the bearer token, converts the
/// backend's structured error JSON into [ApiException], and centralizes
/// timeouts. HTTPS is required in production — see docs/MOBILE_SECURITY.md
/// for the network-security-config/ATS enforcement that must accompany this
/// on each platform (not something Dio itself can guarantee).
class ApiClient {
  ApiClient(this._storage)
      : dio = Dio(
          BaseOptions(
            baseUrl: apiBaseUrl,
            connectTimeout: const Duration(seconds: 15),
            receiveTimeout: const Duration(seconds: 15),
            headers: {'Accept': 'application/json'},
          ),
        ) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // The server picker is hidden (this server is the fixed default), so a
          // base URL saved by an older build is deliberately ignored.
          options.baseUrl = apiBaseUrl;
          final token = await _storage.readToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) {
          handler.next(_translateError(error));
        },
      ),
    );
  }

  final Dio dio;
  final SecureStorageService _storage;

  DioException _translateError(DioException error) {
    final response = error.response;

    if (response == null) {
      return error.copyWith(
          error: ApiException.network(Uri.tryParse(apiBaseUrl)?.authority));
    }

    final data = response.data;
    if (data is Map && data['error'] is Map) {
      final err = data['error'] as Map;
      return error.copyWith(
        error: ApiException(
          code: (err['code'] as String?) ?? 'UNKNOWN_ERROR',
          message: (err['message'] as String?) ?? 'حدث خطأ ما.',
          statusCode: response.statusCode,
          fieldErrors: (err['fields'] as Map?)?.map(
            (k, v) => MapEntry(k as String, (v as List).cast<String>()),
          ),
        ),
      );
    }

    return error.copyWith(error: ApiException.unknown(response.statusMessage));
  }
}

/// Unwraps a DioException back into the [ApiException] the interceptor
/// attached, so calling code never has to know about Dio directly.
ApiException asApiException(Object error) {
  if (error is DioException && error.error is ApiException) {
    return error.error as ApiException;
  }
  if (error is ApiException) return error;
  return ApiException.unknown(error.toString());
}
