import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/shared/widgets/iraqi_plate.dart';
import 'package:qreach_smarttag/shared/widgets/vehicle_thumb.dart';

void main() {
  test(
      'the thumbnail follows the vehicle type only; the plate type never changes it',
      () {
    expect(thumbKindFor(vehicleType: 'sedan'), ThumbKind.sedan);
    expect(thumbKindFor(vehicleType: 'suv'), ThumbKind.suv);
    expect(thumbKindFor(vehicleType: 'pickup'), ThumbKind.pickup);
    expect(thumbKindFor(vehicleType: 'truck'), ThumbKind.truck);
    expect(thumbKindFor(vehicleType: 'bus'), ThumbKind.bus);
    expect(thumbKindFor(vehicleType: 'motorcycle'), ThumbKind.motorcycle);
    expect(thumbKindFor(vehicleType: 'van'), ThumbKind.van);
    expect(thumbKindFor(vehicleType: 'other'), ThumbKind.other);
    expect(
        thumbKindFor(vehicleType: 'sedan', plateType: 'taxi'), ThumbKind.sedan);
    expect(thumbKindFor(vehicleType: 'truck', plateType: 'agricultural'),
        ThumbKind.truck);
  });

  testWidgets('every kind paints without error', (tester) async {
    await tester.pumpWidget(MaterialApp(
      home: Scaffold(
        backgroundColor: const Color(0xFF111111),
        body: Wrap(spacing: 12, runSpacing: 12, children: [
          for (final k in ThumbKind.values)
            RepaintBoundary(child: VehicleThumb(kind: k, width: 96)),
        ]),
      ),
    ));
    expect(tester.takeException(), isNull);
  });

  testWidgets('plates in every colour', (tester) async {
    await tester.pumpWidget(MaterialApp(
      home: Scaffold(
        body: Wrap(spacing: 8, runSpacing: 8, children: [
          for (final t in PlateType.values)
            IraqiPlate(
                number: '12345',
                letter: 'ب',
                governorate: 'بغداد',
                type: t,
                width: 200),
        ]),
      ),
    ));
    expect(tester.takeException(), isNull);
  });
}
