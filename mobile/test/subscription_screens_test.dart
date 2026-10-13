import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/features/profile/data/profile_models.dart';
import 'package:qreach_smarttag/features/profile/data/profile_repository.dart';
import 'package:qreach_smarttag/features/profile/presentation/visibility_screen.dart';
import 'package:qreach_smarttag/features/subscription/data/subscription_models.dart';
import 'package:qreach_smarttag/features/subscription/data/subscription_repository.dart';
import 'package:qreach_smarttag/features/subscription/presentation/subscription_screen.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

const _basic = PlanModel(
  slug: 'basic',
  name: 'الأساسية',
  description: 'المراسلة فقط',
  price: 0,
  currency: 'IQD',
  isFree: true,
  billingPeriodDays: 365,
  publicContacts: ['phone'],
);

const _pro = PlanModel(
  slug: 'pro',
  name: 'الاحترافية',
  description: 'الرسائل وعرض بيانات التواصل',
  price: 25000,
  currency: 'IQD',
  isFree: false,
  billingPeriodDays: 365,
  publicContacts: ['phone', 'instagram', 'other'],
  extraContactMethods: true,
);

class FakeSubscriptionRepository implements SubscriptionRepository {
  FakeSubscriptionRepository({required this.info, this.profile});

  SubscriptionInfo info;
  ProfileModel? profile;
  final List<String> upgradeRequests = [];
  int cancelRenewals = 0;
  int resumeRenewals = 0;
  int trialStarts = 0;

  @override
  Future<SubscriptionInfo> fetch() async => info;

  @override
  Future<void> requestUpgrade(String planSlug) async =>
      upgradeRequests.add(planSlug);

  @override
  Future<void> cancelRenewal() async => cancelRenewals++;

  @override
  Future<void> resumeRenewal() async => resumeRenewals++;

  @override
  Future<void> startTrial() async => trialStarts++;
}

class FakeProfileRepository implements ProfileRepository {
  FakeProfileRepository(this.profile);

  ProfileModel profile;
  int saves = 0;

  @override
  Future<ProfileModel> fetch() async => profile;

  @override
  Future<Uint8List?> fetchAvatar() async => null;

  @override
  Future<ProfileModel> uploadAvatar(String filePath) async => profile;

  @override
  Future<ProfileModel> removeAvatar() async => profile;

  @override
  Future<ProfileModel> updateName(String name) async => profile;

  @override
  Future<ProfileModel> updatePassword(
          {String? currentPassword, required String password}) async =>
      profile;

  @override
  Future<ProfileModel> setShowName(bool value) async => profile;

  @override
  Future<ProfileModel> setShowAvatar(bool value) async => profile;

  @override
  Future<ProfileModel> updatePhone(
      {required String phone, required bool visible}) async {
    saves++;
    return profile;
  }

  @override
  Future<ProfileModel> addMethod({
    required String type,
    required String value,
    String? label,
    bool visible = false,
  }) async {
    saves++;
    return profile;
  }

  @override
  Future<ProfileModel> setMethodVisible(int id, bool visible) async => profile;

  @override
  Future<ProfileModel> removeMethod(int id) async => profile;

  @override
  Future<ProfileModel> saveEmergency({
    required String name,
    required String? relationship,
    required String phone,
    required bool visible,
  }) async {
    saves++;
    return profile;
  }

  @override
  Future<ProfileModel> removeEmergency() async => profile;
}

ProfileModel _profile({required bool plus, String? phone}) => ProfileModel(
      name: 'علي',
      phone: '+9647700000000',
      plan: plus ? 'plus' : 'basic',
      phoneField: ContactField(value: phone, canShow: true),
      canAddMethods: plus,
      platforms: const [
        PlatformInfo(type: 'instagram', label: 'إنستغرام', hint: 'username'),
      ],
      methods: plus
          ? const [
              ContactMethodModel(
                  id: 1,
                  type: 'instagram',
                  label: 'إنستغرام',
                  value: 'my.car',
                  visible: false,
                  canShow: true),
            ]
          : const [],
    );

SubscriptionInfo _info({
  required PlanModel plan,
  bool isDefault = true,
  String? status,
  bool pending = false,
  bool renewalCanceled = false,
  bool canCancelRenewal = false,
  DateTime? graceEndsAt,
  TrialInfo trial = TrialInfo.none,
}) =>
    SubscriptionInfo(
      plan: plan,
      isDefault: isDefault,
      subscriptionStatus: status,
      endsAt: status == null ? null : DateTime.utc(2027, 9, 25),
      plans: const [_basic, _pro],
      pendingUpgrade: pending,
      renewalCanceled: renewalCanceled,
      canCancelRenewal: canCancelRenewal,
      graceEndsAt: graceEndsAt,
      trial: trial,
    );

Widget _app(FakeSubscriptionRepository repo, Widget screen,
    {FakeProfileRepository? profileRepo}) {
  return ProviderScope(
    overrides: [
      subscriptionRepositoryProvider.overrideWithValue(repo),
      if (profileRepo != null)
        profileRepositoryProvider.overrideWithValue(profileRepo),
    ],
    child: MaterialApp.router(
      locale: const Locale('ar'),
      supportedLocales: const [Locale('ar'), Locale('en')],
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      routerConfig: GoRouter(routes: [
        GoRoute(path: '/', builder: (_, __) => screen),
        GoRoute(
            path: '/subscription/checkout/:slug',
            builder: (_, state) => Scaffold(
                body: Text('checkout-route:${state.pathParameters['slug']}'))),
        GoRoute(
            path: '/subscription',
            builder: (_, __) =>
                const Scaffold(body: Text('subscription-route'))),
      ]),
    ),
  );
}

void _tall(WidgetTester tester) {
  tester.view.physicalSize = const Size(800, 3200);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
}

void main() {
  group('SubscriptionScreen', () {
    testWidgets(
        'Basic user sees a free plan, locked pro features and an upgrade button',
        (tester) async {
      _tall(tester);
      final repo = FakeSubscriptionRepository(info: _info(plan: _basic));

      await tester.pumpWidget(_app(repo, const SubscriptionScreen()));
      await tester.pumpAndSettle();

      expect(find.text('الأساسية'), findsWidgets);
      expect(find.text('مجاني'), findsWidgets);
      expect(find.text('الباقة الافتراضية المجانية'), findsOneWidget);
      // Phone, Instagram and extra info are locked on Basic.
      expect(find.byIcon(Icons.lock_outline), findsNWidgets(2));
      expect(find.text('اشترك في هذه الباقة'), findsOneWidget);
    });

    testWidgets(
        'Pro user sees price, status and end date, and no upgrade button',
        (tester) async {
      _tall(tester);
      final repo = FakeSubscriptionRepository(
          info: _info(plan: _pro, isDefault: false, status: 'active'));

      await tester.pumpWidget(_app(repo, const SubscriptionScreen()));
      await tester.pumpAndSettle();

      expect(find.text('25,000 د.ع / سنة'), findsWidgets);
      expect(find.text('فعّال'), findsOneWidget);
      expect(find.text('ينتهي في 2027-09-25'), findsOneWidget);
      expect(find.byIcon(Icons.lock_outline), findsNothing);
      expect(find.text('اشترك في هذه الباقة'), findsNothing);
    });

    testWidgets(
        'a lapsed Pro subscription shows the expiry banner and falls back to Basic',
        (tester) async {
      _tall(tester);
      final repo = FakeSubscriptionRepository(
          info: _info(plan: _basic, status: 'expired'));

      await tester.pumpWidget(_app(repo, const SubscriptionScreen()));
      await tester.pumpAndSettle();

      expect(
          find.textContaining('انتهى اشتراكك في 2027-09-25'), findsOneWidget);
    });

    testWidgets('cancelling the annual plan asks first and keeps the remaining days',
        (tester) async {
      _tall(tester);
      final repo = FakeSubscriptionRepository(
          info: _info(
              plan: _pro,
              isDefault: false,
              status: 'active',
              canCancelRenewal: true));

      await tester.pumpWidget(_app(repo, const SubscriptionScreen()));
      await tester.pumpAndSettle();

      await tester.tap(find.text('إلغاء الاشتراك السنوي'));
      await tester.pumpAndSettle();
      expect(find.textContaining('ولا تخسر أيًّا من الأيام المتبقية'),
          findsOneWidget);

      // Backing out does nothing.
      await tester.tap(find.text('إبقاء الاشتراك'));
      await tester.pumpAndSettle();
      expect(repo.cancelRenewals, 0);

      await tester.tap(find.text('إلغاء الاشتراك السنوي'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('إلغاء التجديد'));
      await tester.pumpAndSettle();
      expect(repo.cancelRenewals, 1);
    });

    testWidgets('a stopped renewal says when the plan ends and can be resumed',
        (tester) async {
      _tall(tester);
      final repo = FakeSubscriptionRepository(
          info: _info(
              plan: _pro,
              isDefault: false,
              status: 'active',
              renewalCanceled: true));

      await tester.pumpWidget(_app(repo, const SubscriptionScreen()));
      await tester.pumpAndSettle();

      expect(find.textContaining('تبقى باقتك فعّالة حتى 2027-09-25'),
          findsOneWidget);
      expect(find.text('إلغاء الاشتراك السنوي'), findsNothing);

      await tester.tap(find.text('إعادة تفعيل التجديد'));
      await tester.pumpAndSettle();
      expect(repo.resumeRenewals, 1);
    });

    testWidgets('the free trial is offered, asks for confirmation, then starts',
        (tester) async {
      _tall(tester);
      final repo = FakeSubscriptionRepository(
          info: _info(
              plan: _basic,
              trial: const TrialInfo(available: true, days: 7)));

      await tester.pumpWidget(_app(repo, const SubscriptionScreen()));
      await tester.pumpAndSettle();

      expect(find.text('جرّب باقة بلس مجانًا لمدة 7 أيام'), findsOneWidget);

      await tester.tap(find.text('ابدأ التجربة المجانية'));
      await tester.pumpAndSettle();
      // The confirmation dialog: nothing started yet.
      expect(repo.trialStarts, 0);
      expect(find.text('بدء التجربة المجانية؟'), findsOneWidget);

      await tester.tap(find.text('ابدأ التجربة المجانية').last);
      await tester.pumpAndSettle();
      expect(repo.trialStarts, 1);
    });

    testWidgets('no trial card once the trial was used or is switched off',
        (tester) async {
      _tall(tester);
      final repo = FakeSubscriptionRepository(
          info: _info(plan: _basic, trial: const TrialInfo(used: true)));

      await tester.pumpWidget(_app(repo, const SubscriptionScreen()));
      await tester.pumpAndSettle();

      expect(find.text('ابدأ التجربة المجانية'), findsNothing);
    });

    testWidgets('a running trial says when it ends; an ended one says so',
        (tester) async {
      _tall(tester);
      final running = FakeSubscriptionRepository(
          info: _info(
              plan: _pro,
              isDefault: false,
              status: 'trialing',
              trial: TrialInfo(
                  used: true, active: true, endsAt: DateTime.utc(2027, 9, 25))));

      await tester.pumpWidget(_app(running, const SubscriptionScreen()));
      await tester.pumpAndSettle();
      expect(find.textContaining('2027-09-25'), findsWidgets);
      expect(find.text('إلغاء الاشتراك السنوي'), findsNothing);

      final ended = FakeSubscriptionRepository(
          info: _info(
              plan: _basic,
              status: 'trial_ended',
              trial: const TrialInfo(used: true)));
      await tester.pumpWidget(_app(ended, const SubscriptionScreen()));
      await tester.pumpAndSettle();
      expect(find.textContaining('انتهت تجربتك المجانية'), findsOneWidget);
    });

    testWidgets('inside the grace week the screen explains it and offers renewal',
        (tester) async {
      _tall(tester);
      final repo = FakeSubscriptionRepository(
          info: _info(
              plan: _pro,
              isDefault: false,
              status: 'grace',
              graceEndsAt: DateTime.utc(2027, 10, 2)));

      await tester.pumpWidget(_app(repo, const SubscriptionScreen()));
      await tester.pumpAndSettle();

      expect(find.textContaining('لديك حتى 2027-10-02 لتجديده'), findsOneWidget);
      expect(find.text('فترة سماح'), findsOneWidget);
      expect(find.text('تجديد الآن'), findsOneWidget);
    });

    testWidgets('a pending upgrade disables the request button',
        (tester) async {
      _tall(tester);
      final repo =
          FakeSubscriptionRepository(info: _info(plan: _basic, pending: true));

      await tester.pumpWidget(_app(repo, const SubscriptionScreen()));
      await tester.pumpAndSettle();

      expect(find.text('طلبك قيد المراجعة'), findsOneWidget);
      expect(find.text('اشترك في هذه الباقة'), findsNothing);
    });

    testWidgets('the subscribe button opens the checkout for that plan',
        (tester) async {
      _tall(tester);
      final repo = FakeSubscriptionRepository(info: _info(plan: _basic));

      await tester.pumpWidget(_app(repo, const SubscriptionScreen()));
      await tester.pumpAndSettle();

      await tester.ensureVisible(find.text('اشترك في هذه الباقة'));
      await tester.tap(find.text('اشترك في هذه الباقة'));
      await tester.pumpAndSettle();

      expect(find.text('checkout-route:pro'), findsOneWidget);
      expect(repo.upgradeRequests, isEmpty,
          reason: 'no admin-approval request is sent any more');
    });
  });

  group('VisibilityScreen', () {
    Future<void> open(WidgetTester tester, ProfileModel p) async {
      tester.view.physicalSize = const Size(800, 3600);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final repo = FakeSubscriptionRepository(info: _info(plan: _basic));
      await tester.pumpWidget(_app(repo, const VisibilityScreen(),
          profileRepo: FakeProfileRepository(p)));
      await tester.pumpAndSettle();
    }

    testWidgets('Basic: extra methods are locked behind an upgrade to Plus',
        (tester) async {
      await open(tester, _profile(plus: false, phone: '+9647701234567'));

      expect(find.text('متاح في باقة Plus'), findsOneWidget);
      expect(find.text('إضافة طريقة تواصل'), findsNothing);
      expect(find.text('+9647701234567'), findsOneWidget);

      await tester.ensureVisible(find.text('الترقية إلى Plus'));
      await tester.tap(find.text('الترقية إلى Plus'));
      await tester.pumpAndSettle();
      expect(find.text('subscription-route'), findsOneWidget);
    });

    testWidgets('Plus: sees the add button and existing methods, all hidden',
        (tester) async {
      await open(tester, _profile(plus: true));

      expect(find.text('إضافة طريقة تواصل'), findsOneWidget);
      expect(find.text('my.car'), findsOneWidget);
      expect(find.text('متاح في باقة Plus'), findsNothing);
    });

    testWidgets('everything is hidden by default', (tester) async {
      await open(tester, _profile(plus: true, phone: '+9647701234567'));

      final switches = tester.widgetList<Switch>(find.byType(Switch)).toList();
      expect(switches.every((s) => s.value == false), isTrue);
      final tiles = tester
          .widgetList<SwitchListTile>(find.byType(SwitchListTile))
          .toList();
      expect(tiles.every((s) => s.value == false), isTrue);
    });
  });
}
