import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/network/api_client.dart';
import 'package:qreach_smarttag/core/storage/secure_storage_service.dart';

class _Storage extends SecureStorageService {
  _Storage(this.token);
  final String? token;

  @override
  Future<String?> readToken() async => token;
}

/// Answers every request with the given status/body.
class _Adapter implements HttpClientAdapter {
  _Adapter(this.status, this.body);
  final int status;
  final Map<String, dynamic> body;

  @override
  Future<ResponseBody> fetch(RequestOptions options,
      Stream<Uint8List>? requestStream, Future<void>? cancelFuture) async {
    return ResponseBody.fromString(jsonEncode(body), status, headers: {
      Headers.contentTypeHeader: [Headers.jsonContentType]
    });
  }

  @override
  void close({bool force = false}) {}
}

ApiClient _client(String? token, List<String> codes,
    {int status = 401, String code = 'SESSION_REPLACED'}) {
  final client = ApiClient(_Storage(token), onSessionInvalid: codes.add);
  client.dio.httpClientAdapter = _Adapter(status, {
    'error': {'code': code, 'message': 'x'}
  });
  return client;
}

void main() {
  test('a 401 on a request that carried a token ends the session', () async {
    final codes = <String>[];
    final client = _client('t', codes);

    await expectLater(
        client.dio.get('/vehicles'), throwsA(isA<DioException>()));

    expect(codes, ['SESSION_REPLACED']);
  });

  test('a 401 without a stored token is just a failed request', () async {
    final codes = <String>[];
    final client = _client(null, codes, code: 'UNAUTHENTICATED');

    await expectLater(
        client.dio.get('/vehicles'), throwsA(isA<DioException>()));

    expect(codes, isEmpty);
  });

  test('logout itself failing with 401 does not re-trigger the handler',
      () async {
    final codes = <String>[];
    final client = _client('t', codes, code: 'UNAUTHENTICATED');

    await expectLater(
        client.dio.post('/auth/logout'), throwsA(isA<DioException>()));

    expect(codes, isEmpty);
  });

  test('other errors (e.g. validation) do not end the session', () async {
    final codes = <String>[];
    final client = _client('t', codes, status: 422, code: 'INVALID');

    await expectLater(
        client.dio.get('/vehicles'), throwsA(isA<DioException>()));

    expect(codes, isEmpty);
  });
}
