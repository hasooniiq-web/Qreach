import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/network/api_exception.dart';
import 'package:qreach_smarttag/features/auth/presentation/auth_errors.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

void main() {
  late AppLocalizations ar;
  late AppLocalizations en;

  setUpAll(() async {
    ar = await AppLocalizations.delegate.load(const Locale('ar'));
    en = await AppLocalizations.delegate.load(const Locale('en'));
  });

  group('waitText', () {
    test('says the wait the way a person does', () {
      expect(waitText(ar, 30), 'دقيقة');
      expect(waitText(ar, 300), '5 دقائق');
      expect(waitText(ar, 3600), 'ساعة');
      expect(waitText(ar, 3599), 'ساعة');
      expect(waitText(ar, 7200), 'ساعتين');
      expect(waitText(ar, 36000), '10 ساعات');
      expect(waitText(ar, 86400), 'يوم');
      expect(waitText(ar, 259200), '3 أيام');
      expect(waitText(en, 3600), 'an hour');
      expect(waitText(en, 36000), '10 hours');
      expect(waitText(en, 604800), '7 days');
    });
  });

  group('authErrorMessage', () {
    test('a locked account says when to try again, in the app language', () {
      const e = ApiException(
          code: 'AUTH_LOCKED', message: 'x', statusCode: 429, retryAfter: 3600);

      expect(authErrorMessage(ar, e), contains('بعد ساعة'));
      expect(authErrorMessage(en, e), 'Sign-in is paused after 3 wrong attempts. Try again in an hour.');
    });

    test('a wrong password shows how many tries are left', () {
      const e = ApiException(
          code: 'INVALID_CREDENTIALS',
          message: 'رقم الهاتف أو كلمة المرور غير صحيحة.',
          statusCode: 422,
          attemptsLeft: 2);

      final text = authErrorMessage(ar, e);
      expect(text, contains('رقم الهاتف أو كلمة المرور غير صحيحة.'));
      expect(text, contains('محاولتان'));
    });

    test('too many codes asked for', () {
      const e = ApiException(
          code: 'OTP_LIMIT', message: 'x', statusCode: 429, retryAfter: 36000);

      expect(authErrorMessage(en, e), contains('10 hours'));
    });

    test('anything else keeps the server message', () {
      const e = ApiException(code: 'SOMETHING', message: 'رسالة الخادم');

      expect(authErrorMessage(ar, e), 'رسالة الخادم');
    });
  });
}
