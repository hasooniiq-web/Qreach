import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/features/vehicles/data/vehicle_model.dart';
import 'package:qreach_smarttag/features/vehicles/presentation/vehicle_detail_screen.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';
import 'package:qreach_smarttag/shared/widgets/iraqi_plate.dart';

import 'support/preview_support.dart';

Widget _plate(IraqiPlate plate) => MaterialApp(
      home: Scaffold(body: Center(child: plate)),
    );

Widget _detail(FakeVehicles repo) => ProviderScope(
      overrides: [vehicleRepositoryProvider.overrideWithValue(repo)],
      child: MaterialApp(
        locale: const Locale('ar'),
        supportedLocales: const [Locale('ar'), Locale('en')],
        localizationsDelegates: const [
          AppLocalizations.delegate,
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        home: VehicleDetailScreen(vehicleId: repo.items.first.id),
      ),
    );

void _tall(WidgetTester tester) {
  tester.view.physicalSize = const Size(800, 2400);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
}

void main() {
  group('governorates', () {
    test('every governorate has its plate number, 11 to 29', () {
      expect(iraqGovernorates.length, 19);
      for (final g in iraqGovernorates) {
        expect(iraqGovernorateCodes[g], isNotNull, reason: g);
      }
      expect(iraqGovernorateCodes['بغداد'], 11);
      expect(iraqGovernorateCodes['البصرة'], 14);
      expect(iraqGovernorateCodes['واسط'], 29);
      expect(iraqGovernorateCodes.values.toSet().length, 19);
    });

    test('an Arabic plate letter has a Latin form', () {
      expect(plateLatinLetter('ك'), 'K');
      expect(plateLatinLetter('ب'), 'B');
      expect(plateLatinLetter('g'), 'G');
      expect(plateLatinLetter(''), '');
    });
  });

  group('plate designs', () {
    testWidgets('english: governorate number, Latin letter and number', (tester) async {
      await tester.pumpWidget(_plate(const IraqiPlate(
        number: '34567',
        letter: 'ج',
        governorate: 'البصرة',
        type: PlateType.government,
        style: PlateStyle.english,
        width: 300,
      )));

      expect(find.text('14'), findsOneWidget);
      expect(find.text('J'), findsOneWidget);
      expect(find.text('34567'), findsOneWidget);
      expect(find.text('I'), findsOneWidget);
      expect(find.text('Q'), findsOneWidget);
    });

    testWidgets('german: Arabic digits, Latin line, governorate and category',
        (tester) async {
      await tester.pumpWidget(_plate(const IraqiPlate(
        number: '25080',
        letter: 'ك',
        governorate: 'بغداد',
        type: PlateType.private,
        style: PlateStyle.german,
        width: 300,
      )));

      expect(find.text('٢٥٠٨٠'), findsOneWidget);
      expect(find.text('ك'), findsOneWidget);
      expect(find.text('K 2 5 0 8 0'), findsOneWidget);
      expect(find.text('بغداد'), findsOneWidget);
      expect(find.text('خصوصي'), findsOneWidget);
    });

    testWidgets('other: the plain card keeps showing the number as typed',
        (tester) async {
      await tester.pumpWidget(_plate(const IraqiPlate(
        number: '65600',
        letter: 'ب',
        governorate: 'بغداد',
      )));

      expect(find.text('65600'), findsOneWidget);
      expect(find.text('العراق'), findsOneWidget);
    });

    test('a vehicle without a stored style keeps the plain card', () {
      final v = VehicleModel.fromJson(
          {'id': 'v', 'plate_number': '1', 'plate_type': 'private'});
      expect(v.plateStyle, 'other');
      expect(v.lockedByPlan, isFalse);

      final e = VehicleModel.fromJson({
        'id': 'v',
        'plate_number': '1',
        'plate_style': 'english',
        'locked_by_plan': true,
      });
      expect(e.plateStyle, 'english');
      expect(e.lockedByPlan, isTrue);
      expect(e.toCreateJson()['plate_style'], 'english');
    });
  });

  group('vehicle page', () {
    testWidgets('the plate number is not repeated as a name', (tester) async {
      _tall(tester);
      final repo = FakeVehicles([
        const VehicleModel(id: 'v-1', plateNumber: '65600', vehicleType: 'sedan'),
      ]);
      await tester.pumpWidget(_detail(repo));
      await tester.pumpAndSettle();

      // Only the plate itself shows it.
      expect(find.text('65600'), findsOneWidget);
    });

    testWidgets('a nickname is shown as the name', (tester) async {
      _tall(tester);
      final repo = FakeVehicles([
        const VehicleModel(
            id: 'v-1',
            plateNumber: '65600',
            vehicleType: 'sedan',
            nickname: 'سيارة العمل'),
      ]);
      await tester.pumpWidget(_detail(repo));
      await tester.pumpAndSettle();

      expect(find.text('سيارة العمل'), findsOneWidget);
    });

    testWidgets('a vehicle the plan no longer covers explains it and cannot be linked',
        (tester) async {
      _tall(tester);
      final repo = FakeVehicles([
        const VehicleModel(
            id: 'v-1',
            plateNumber: '65600',
            vehicleType: 'sedan',
            lockedByPlan: true),
      ]);
      await tester.pumpWidget(_detail(repo));
      await tester.pumpAndSettle();

      expect(find.textContaining('غير فعّالة لأن باقتك الحالية'), findsOneWidget);
      expect(find.text('تفعيل الباقة'), findsOneWidget);
      expect(find.text('ربط رمز جديد'), findsNothing);
    });
  });
}
