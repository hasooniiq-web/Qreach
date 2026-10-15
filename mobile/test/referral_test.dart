import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/network/api_exception.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/features/referral/data/referral_repository.dart';
import 'package:qreach_smarttag/features/referral/presentation/referral_screen.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

class _FakeReferrals implements ReferralRepository {
  _FakeReferrals({this.info, this.error});

  ReferralInfo? info;
  final ApiException? error;
  final List<String> applied = [];

  static const base = ReferralInfo(
    code: 'K7M2-P9QX',
    rewardDays: 30,
    enabled: true,
    canApply: true,
    referred: false,
    invited: 3,
    rewarded: 1,
    earnedDays: 30,
  );

  @override
  Future<ReferralInfo> fetch() async => info ?? base;

  @override
  Future<ReferralInfo> apply(String code) async {
    applied.add(code);
    if (error != null) throw error!;
    return base;
  }
}

Widget _app(ReferralRepository repo) => ProviderScope(
      overrides: [referralRepositoryProvider.overrideWithValue(repo)],
      child: MaterialApp(
        locale: const Locale('ar'),
        supportedLocales: const [Locale('ar'), Locale('en')],
        localizationsDelegates: const [
          AppLocalizations.delegate,
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        home: const ReferralScreen(),
      ),
    );

void _tall(WidgetTester tester) {
  tester.view.physicalSize = const Size(900, 2400);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
}

void main() {
  group('ReferralInfo', () {
    test('reads the server payload', () {
      final info = ReferralInfo.fromJson({
        'code': 'AB12-CD34',
        'reward_days': 30,
        'enabled': true,
        'can_apply': false,
        'referred': true,
        'invited': 4,
        'rewarded': 2,
        'earned_days': 60,
      });

      expect(info.code, 'AB12-CD34');
      expect(info.rewardDays, 30);
      expect(info.canApply, isFalse);
      expect(info.earnedDays, 60);
    });
  });

  group('ReferralScreen', () {
    testWidgets('shows the code, the reward and what it earned', (tester) async {
      _tall(tester);
      await tester.pumpWidget(_app(_FakeReferrals()));
      await tester.pumpAndSettle();

      expect(find.text('K7M2-P9QX'), findsOneWidget);
      expect(find.text('ادعُ أصدقاءك: عندما ينضم 2 منهم تحصل على 30 يومًا من Plus مجانًا'),
          findsOneWidget);
      expect(find.text('المدعوون'), findsOneWidget);
      expect(find.text('3'), findsOneWidget);
      expect(find.text('مشاركة الدعوة'), findsOneWidget);
    });

    testWidgets('entering a friend\'s code sends it to the server', (tester) async {
      _tall(tester);
      final repo = _FakeReferrals();
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField), 'ab12-cd34');
      await tester.tap(find.text('تأكيد الرمز'));
      await tester.pumpAndSettle();

      expect(repo.applied, ['ab12-cd34']);
      expect(find.text('تم حفظ رمز الدعوة. حُسبت دعوتك لصاحب الرمز.'),
          findsOneWidget);
    });

    testWidgets('a short code is refused before anything is sent', (tester) async {
      _tall(tester);
      final repo = _FakeReferrals();
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField), 'AB12');
      await tester.tap(find.text('تأكيد الرمز'));
      await tester.pumpAndSettle();

      expect(repo.applied, isEmpty);
      expect(find.text('رمز الدعوة غير صحيح. تأكد منه وأعد المحاولة.'),
          findsOneWidget);
    });

    testWidgets('the server\'s refusal is explained in the app language',
        (tester) async {
      _tall(tester);
      final repo = _FakeReferrals(
          error: ApiException(
              code: 'REFERRAL_SELF', message: 'x', statusCode: 422));
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField), 'K7M2-P9QX');
      await tester.tap(find.text('تأكيد الرمز'));
      await tester.pumpAndSettle();

      expect(find.text('لا يمكنك استخدام رمز دعوتك أنت.'), findsOneWidget);
    });

    testWidgets('no code field once the customer has used one or paid',
        (tester) async {
      _tall(tester);
      final repo = _FakeReferrals(
          info: const ReferralInfo(
              code: 'K7M2-P9QX',
              rewardDays: 30,
              enabled: true,
              canApply: false,
              referred: true,
              invited: 0,
              rewarded: 0,
              earnedDays: 0));
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      expect(find.byType(TextField), findsNothing);
      expect(find.text('لديّ رمز دعوة من صديق'), findsNothing);
    });

    testWidgets('a switched-off programme says so and offers no share',
        (tester) async {
      _tall(tester);
      final repo = _FakeReferrals(
          info: const ReferralInfo(
              code: 'K7M2-P9QX',
              rewardDays: 0,
              enabled: false,
              canApply: false,
              referred: false,
              invited: 0,
              rewarded: 0,
              earnedDays: 0));
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      expect(find.text('الدعوات متوقفة حاليًا.'), findsOneWidget);
      expect(find.text('مشاركة الدعوة'), findsNothing);
    });
  });
}
