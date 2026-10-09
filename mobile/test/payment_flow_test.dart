import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/features/subscription/data/payment_models.dart';
import 'package:qreach_smarttag/features/subscription/data/payment_repository.dart';
import 'package:qreach_smarttag/features/subscription/data/subscription_models.dart';
import 'package:qreach_smarttag/features/subscription/data/subscription_repository.dart';
import 'package:qreach_smarttag/features/subscription/presentation/checkout_screen.dart';
import 'package:qreach_smarttag/features/subscription/presentation/payment_status_screen.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

const _pro = PlanModel(
  slug: 'pro',
  name: 'الاحترافية',
  description: null,
  price: 25000,
  currency: 'IQD',
  isFree: false,
  billingPeriodDays: 365,
  publicContacts: ['phone', 'instagram', 'other'],
  extraContactMethods: true,
);

class _Subscriptions implements SubscriptionRepository {
  @override
  Future<SubscriptionInfo> fetch() async => const SubscriptionInfo(
        plan: _pro,
        isDefault: true,
        subscriptionStatus: null,
        endsAt: null,
        plans: [_pro],
        pendingUpgrade: false,
      );

  @override
  Future<void> requestUpgrade(String planSlug) async {}
}

class _Payments implements PaymentRepository {
  _Payments({this.methodList = const [], List<PaymentModel>? fetches})
      : fetches = fetches ?? [];

  final List<PaymentMethodModel> methodList;

  /// Responses for successive fetch() calls; the last one repeats.
  final List<PaymentModel> fetches;
  final List<Map<String, String>> started = [];
  int fetchCount = 0;

  @override
  Future<List<PaymentMethodModel>> methods() async => methodList;

  @override
  Future<StartedPayment> start({
    required String planSlug,
    required String gateway,
    required String idempotencyKey,
  }) async {
    started.add({'plan': planSlug, 'gateway': gateway, 'key': idempotencyKey});
    return StartedPayment(
      payment: _payment(PaymentStatus.pending),
      checkoutUrl: 'https://pay.example/checkout/abc',
    );
  }

  @override
  Future<PaymentModel> fetch(String id) async {
    final i = fetchCount < fetches.length ? fetchCount : fetches.length - 1;
    fetchCount++;
    return fetches[i];
  }
}

PaymentModel _payment(String status, {String? reason, bool test = false}) =>
    PaymentModel(
      id: 'p1',
      status: status,
      plan: 'الاحترافية',
      amount: 25000,
      currency: 'IQD',
      gateway: 'بوابة تجريبية',
      testMode: test,
      receiptNumber:
          status == PaymentStatus.succeeded ? 'QR-20261010-ABC123' : null,
      failureReason: reason,
      createdAt: DateTime.utc(2026, 10, 10, 9),
      paidAt: status == PaymentStatus.succeeded
          ? DateTime.utc(2026, 10, 10, 9, 1)
          : null,
      periodEndsAt:
          status == PaymentStatus.succeeded ? DateTime.utc(2027, 10, 10) : null,
    );

Widget _app(_Payments payments, Widget screen) => ProviderScope(
      overrides: [
        subscriptionRepositoryProvider.overrideWithValue(_Subscriptions()),
        paymentRepositoryProvider.overrideWithValue(payments),
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
              path: '/subscription/payment/:id',
              builder: (_, state) => PaymentStatusScreen(
                    paymentId: state.pathParameters['id']!,
                    pollInterval: const Duration(milliseconds: 50),
                  )),
        ]),
      ),
    );

void _tall(WidgetTester tester) {
  tester.view.physicalSize = const Size(800, 3200);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
}

void main() {
  group('CheckoutScreen', () {
    testWidgets('shows the plan price and benefits and the offered methods',
        (tester) async {
      _tall(tester);
      final payments = _Payments(methodList: const [
        PaymentMethodModel(
            id: 'sandbox', name: 'بوابة تجريبية', testMode: true),
        PaymentMethodModel(id: 'other', name: 'محفظة'),
      ]);

      await tester
          .pumpWidget(_app(payments, const CheckoutScreen(planSlug: 'pro')));
      await tester.pumpAndSettle();

      expect(find.text('25,000 د.ع / سنة'), findsOneWidget);
      expect(find.text('بوابة تجريبية'), findsOneWidget);
      expect(find.text('تجريبي'), findsOneWidget);
      expect(find.text('محفظة'), findsOneWidget);
    });

    testWidgets('paying needs a chosen method; the app sends no price',
        (tester) async {
      _tall(tester);
      final opened = <Uri>[];
      final payments = _Payments(
        methodList: const [
          PaymentMethodModel(id: 'sandbox', name: 'بوابة تجريبية'),
          PaymentMethodModel(id: 'other', name: 'محفظة'),
        ],
        fetches: [_payment(PaymentStatus.pending)],
      );

      await tester.pumpWidget(_app(
          payments,
          CheckoutScreen(
              planSlug: 'pro',
              openUrl: (u) async {
                opened.add(u);
                return true;
              })));
      await tester.pumpAndSettle();

      // Two methods and none chosen yet: "Pay now" is disabled.
      await tester.tap(find.text('ادفع الآن'));
      await tester.pump();
      expect(payments.started, isEmpty);

      await tester.tap(find.text('محفظة'));
      await tester.pump();
      await tester.tap(find.text('ادفع الآن'));
      await tester.pump(const Duration(milliseconds: 200));
      await tester.pump(const Duration(milliseconds: 600)); // route transition

      expect(payments.started, hasLength(1));
      expect(payments.started.single['plan'], 'pro');
      expect(payments.started.single['gateway'], 'other');
      expect(payments.started.single['key'], isNotEmpty);
      expect(opened.single.toString(), 'https://pay.example/checkout/abc');
      // It then waits on the status screen rather than assuming success.
      expect(find.text('جارٍ تأكيد الدفع…'), findsOneWidget);
    });

    testWidgets('with no method available the user is told and cannot pay',
        (tester) async {
      _tall(tester);
      final payments = _Payments();

      await tester
          .pumpWidget(_app(payments, const CheckoutScreen(planSlug: 'pro')));
      await tester.pumpAndSettle();

      expect(find.byKey(const ValueKey('no-methods')), findsOneWidget);
      await tester.tap(find.text('ادفع الآن'));
      await tester.pump();
      expect(payments.started, isEmpty);
    });
  });

  group('PaymentStatusScreen', () {
    testWidgets('waits while pending, then shows the receipt once confirmed',
        (tester) async {
      _tall(tester);
      final payments = _Payments(fetches: [
        _payment(PaymentStatus.pending),
        _payment(PaymentStatus.pending),
        _payment(PaymentStatus.succeeded, test: true),
      ]);

      await tester.pumpWidget(_app(
          payments,
          const PaymentStatusScreen(
              paymentId: 'p1', pollInterval: Duration(milliseconds: 50))));
      await tester.pump();
      expect(find.text('جارٍ تأكيد الدفع…'), findsOneWidget);
      expect(find.text('QR-20261010-ABC123'), findsNothing);

      await tester.pump(const Duration(milliseconds: 60));
      await tester.pump(const Duration(milliseconds: 60));
      await tester.pumpAndSettle();

      expect(find.text('تمت عملية الدفع'), findsOneWidget);
      expect(find.text('QR-20261010-ABC123'), findsOneWidget);
      expect(find.text('25,000 د.ع'), findsOneWidget);
      expect(find.textContaining('2027-10-10'), findsOneWidget);
      expect(find.textContaining('دفع تجريبية'), findsOneWidget);
    });

    testWidgets('a failed payment says so and offers to try again',
        (tester) async {
      _tall(tester);
      final payments = _Payments(
          fetches: [_payment(PaymentStatus.failed, reason: 'declined')]);

      await tester.pumpWidget(_app(
          payments,
          const PaymentStatusScreen(
              paymentId: 'p1', pollInterval: Duration(milliseconds: 50))));
      await tester.pumpAndSettle();

      expect(find.text('لم تكتمل عملية الدفع'), findsOneWidget);
      expect(find.text('حاول مرة أخرى'), findsOneWidget);
      expect(find.text('QR-20261010-ABC123'), findsNothing);
    });

    testWidgets('an expired payment explains why', (tester) async {
      _tall(tester);
      final payments = _Payments(
          fetches: [_payment(PaymentStatus.failed, reason: 'expired')]);

      await tester.pumpWidget(_app(
          payments,
          const PaymentStatusScreen(
              paymentId: 'p1', pollInterval: Duration(milliseconds: 50))));
      await tester.pumpAndSettle();

      expect(find.text('انتهت مهلة الدفع قبل إتمامه.'), findsOneWidget);
    });
  });
}
