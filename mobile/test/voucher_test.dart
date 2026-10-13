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

// A serial in the current format: 12 characters, the last one a check character.
const _valid = 'K7M2P9QX4TRC';

// A serial from before that format: 16 digits with a Luhn check digit.
const _legacy = '4539578763621486';

void main() {
  group('VoucherCode', () {
    test('parses a serial typed with spaces, dashes, lower case or a link', () {
      expect(VoucherCode.parse('K7M2 P9QX 4TRC'), _valid);
      expect(VoucherCode.parse('k7m2-p9qx-4trc'), _valid);
      expect(VoucherCode.parse('https://qreach.online/v/$_valid'), _valid);
      expect(VoucherCode.parse('K7M2 P9QX 4TR'), isNull);
      // 0, 1, I, L and O are not in the alphabet: such a serial cannot be real.
      expect(VoucherCode.parse('K7M2 P9QX 4TRO'), isNull);
      expect(VoucherCode.parse('K7M2 P9QX 4TR1'), isNull);
      expect(VoucherCode.parse(''), isNull);
    });

    test('the check character catches a mistyped or swapped character', () {
      expect(VoucherCode.isValid(_valid), isTrue);
      expect(VoucherCode.isValid('K7M2P9QX4TRD'), isFalse);
      expect(VoucherCode.isValid('K7M2P9QX4TCR'), isFalse); // last two swapped
      expect(VoucherCode.isValid('K7M2'), isFalse);
    });

    test('agrees with the server on a second serial', () {
      expect(VoucherCode.isValid('ABCDEFGHJKMU'), isTrue);
      expect(VoucherCode.isValid('3TD4RRRTXY8V'), isTrue);
    });

    test('older 16-digit serials are still understood', () {
      expect(VoucherCode.parse('4539 5787 6362 1486'), _legacy);
      expect(VoucherCode.parse('٤٥٣٩ ٥٧٨٧ ٦٣٦٢ ١٤٨٦'), _legacy);
      expect(VoucherCode.isValid(_legacy), isTrue);
      expect(VoucherCode.isValid('4539578763621487'), isFalse);
    });

    test('groups in fours', () {
      expect(VoucherCode.group(_valid), 'K7M2 P9QX 4TRC');
      expect(VoucherCode.group(_legacy), '4539 5787 6362 1486');
      expect(VoucherCode.group('K7M2P'), 'K7M2 P');
    });
  });

  group('VoucherScreen', () {
    testWidgets('typing a valid number activates the plan and shows the result',
        (tester) async {
      final repo = _FakeVouchers();
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField), 'k7m2p9qx4trc');
      await tester.pump();
      // Upper-cased and grouped while typing.
      expect(find.text('K7M2 P9QX 4TRC'), findsOneWidget);

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

      await tester.enterText(find.byType(TextField), 'K7M2P9QX4TRD');
      await tester.tap(find.text('تفعيل'));
      await tester.pumpAndSettle();

      expect(repo.sent, isEmpty);
      expect(find.textContaining('12 خانة'), findsWidgets);
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
