import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/features/home/presentation/home_screen.dart';
import 'package:qreach_smarttag/features/tags/data/tag_model.dart';
import 'package:qreach_smarttag/features/vehicles/data/vehicle_model.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';
import 'package:qreach_smarttag/shared/widgets/iraqi_plate.dart';

Widget _wrap(Widget child, {String locale = 'ar'}) => MaterialApp(
      locale: Locale(locale),
      supportedLocales: const [Locale('ar'), Locale('en')],
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: Scaffold(body: child),
    );

void main() {
  const linkedTag = TagModel(
    token: 't',
    serialNumber: 'ST-1',
    type: 'qr',
    status: 'assigned',
    publicUrl: 'http://x/t/t',
  );

  testWidgets('carousel shows linked/not linked, owner and a coloured plate',
      (tester) async {
    tester.view.physicalSize = const Size(800, 1200);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(_wrap(VehicleCarousel(
      autoplay: false,
      ownerName: 'علي',
      vehicles: const [
        VehicleModel(
          id: '1',
          plateNumber: '12345',
          plateLetter: 'ب',
          plateGovernorate: 'بغداد',
          plateType: 'taxi',
          vehicleType: 'sedan',
          tags: [linkedTag],
        ),
        VehicleModel(id: '2', plateNumber: '999', plateType: 'government'),
      ],
    )));
    await tester.pumpAndSettle();

    expect(find.text('مربوطة'), findsOneWidget);
    expect(find.textContaining('علي'), findsWidgets);
    expect(find.text('12345'), findsOneWidget);
    expect(find.text('بغداد'), findsOneWidget);
    expect(find.text('أجرة'), findsOneWidget);

    final plate = tester.widget<IraqiPlate>(find.byType(IraqiPlate).first);
    expect(plate.type, PlateType.taxi);
    expect(plate.type.background, const Color(0xFFC62828));

    // Swipe to the second vehicle: unlinked, government (blue).
    await tester.drag(find.byType(PageView), const Offset(-600, 0));
    await tester.pumpAndSettle();
    expect(find.text('غير مربوطة'), findsOneWidget);
    expect(PlateType.government.background, const Color(0xFF1F4E9C));
  });

  test('every plate type has its own colour', () {
    final colours = PlateType.values.map((t) => t.background).toSet();
    expect(colours.length, PlateType.values.length);
    expect(PlateType.fromCode('nonsense'), PlateType.private);
  });
}
