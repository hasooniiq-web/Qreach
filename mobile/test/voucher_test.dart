import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/network/api_exception.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/features/subscription/data/voucher_code.dart';
import 'package:qreach_smarttag/features/subscription/data/voucher_repository.dart';
import 'package:qreach_smarttag/features/subscription/presentation/voucher_screen.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

class _FakeVouchers implements VoucherRepository {
  _FakeVouchers({this.error});

  final ApiException? error;
  final List<String> sent = [];

  @override
  Future<VoucherRedemption> redeem(String digits) async {
    sent.add(digits);
    if (error != null) throw error!;
    return VoucherRedemption(
        planName: 'بلس', days: 365, endsAt: DateTime.utc(2027, 10, 12));
  }
}

Widget _app(VoucherRepository repo) => ProviderScope(
      overrides: [voucherRepositoryProvider.overrideWithValue(repo)],
      child: MaterialApp(
        locale: const Locale('ar'),
        supportedLocales: const [Locale('ar'), Locale('en')],
        localizationsDelegates: const [
          AppLocalizations.delegate,
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        home: const VoucherScreen(),
      ),
    );

// A Luhn-valid 16-digit number.
const _valid = '4539578763621486';

void main() {
  group('VoucherCode', () {
    test('parses digits typed with spaces, dashes, Arabic-Indic digits or a link', () {
      expect(VoucherCode.parse('4539 5787 6362 1486'), _valid);
      expect(VoucherCode.parse('4539-5787-6362-1486'), _valid);
      expect(VoucherCode.parse('٤٥٣٩ ٥٧٨٧ ٦٣٦٢ ١٤٨٦'), _valid);
      expect(VoucherCode.parse('https://qreach.online/v/$_valid'), _valid);
      expect(VoucherCode.parse('4539 5787 6362 148'), isNull);
      expect(VoucherCode.parse(''), isNull);
    });

    test('the check digit catches a mistyped digit', () {
      expect(VoucherCode.isValid(_valid), isTrue);
      expect(VoucherCode.isValid('4539578763621487'), isFalse);
      expect(VoucherCode.isValid('1234'), isFalse);
    });

    test('groups in fours', () {
      expect(VoucherCode.group(_valid), '4539 5787 6362 1486');
      expect(VoucherCode.group('45395'), '4539 5');
    });
  });

  group('VoucherScreen', () {
    testWidgets('typing a valid number activates the plan and shows the result',
        (tester) async {
      final repo = _FakeVouchers();
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField), _valid);
      await tester.pump();
      // Grouped while typing.
      expect(find.text('4539 5787 6362 1486'), findsOneWidget);

      await tester.tap(find.text('تفعيل'));
      await tester.pumpAndSettle();

      expect(repo.sent, [_valid]);
      expect(find.textContaining('تم تفعيل باقة بلس'), findsOneWidget);
      expect(find.textContaining('2027-10-12'), findsOneWidget);
    });

    testWidgets('a mistyped number is rejected before any request',
        (tester) async {
      final repo = _FakeVouchers();
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField), '4539578763621487');
      await tester.tap(find.text('تفعيل'));
      await tester.pumpAndSettle();

      expect(repo.sent, isEmpty);
      expect(find.textContaining('16 رقمًا'), findsOneWidget);
    });

    testWidgets('a used voucher shows its own message', (tester) async {
      final repo = _FakeVouchers(
          error: const ApiException(code: 'VOUCHER_USED', message: 'x', statusCode: 422));
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField), _valid);
      await tester.tap(find.text('تفعيل'));
      await tester.pumpAndSettle();

      expect(find.text('هذه القسيمة مستخدمة من قبل.'), findsOneWidget);
    });
  });
}
