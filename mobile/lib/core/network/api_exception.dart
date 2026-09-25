/// Mirrors the backend's structured error shape: {"error": {"code", "message"}}
/// (see docs/API.md). Screens should switch on [code] for known cases
/// (e.g. TAG_ALREADY_ACTIVATED) and fall back to [message] otherwise.
class ApiException implements Exception {
  const ApiException({
    required this.code,
    required this.message,
    this.statusCode,
    this.fieldErrors,
  });

  final String code;
  final String message;
  final int? statusCode;
  final Map<String, List<String>>? fieldErrors;

  /// [host] is shown so a failed connection says *where* it tried to go —
  /// the usual cause on a phone is a wrong or unreachable server address.
  factory ApiException.network([String? host]) => ApiException(
        code: 'NETWORK_ERROR',
        message: host == null
            ? 'تعذر الاتصال بالخادم. تحقق من اتصالك بالإنترنت.'
            : 'تعذر الاتصال بالخادم ($host). تحقق من الشبكة وأن الخادم يعمل.',
      );

  factory ApiException.unknown([String? detail]) => ApiException(
        code: 'UNKNOWN_ERROR',
        message: detail ?? 'حدث خطأ ما. حاول مرة أخرى.',
      );

  @override
  String toString() => 'ApiException($code): $message';
}
