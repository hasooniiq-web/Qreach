import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/features/vehicles/data/vehicle_model.dart';
import 'package:qreach_smarttag/features/vehicles/presentation/vehicle_detail_screen.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

import 'support/preview_support.dart';

class _RecordingVehicles extends FakeVehicles {
  _RecordingVehicles(super.items);
  final List<String> calls = [];

  @override
  Future<void> setStatus(String id, {required bool active}) async =>
      calls.add('status:$id:$active');
  @override
  Future<void> delete(String id) async => calls.add('delete:$id');
}

Widget _app(_RecordingVehicles repo) => ProviderScope(
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

void main() {
  testWidgets('no three-dot menu; disable and delete are two buttons',
      (tester) async {
    tester.view.physicalSize = const Size(800, 2000);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    final repo = _RecordingVehicles(sampleVehicles);
    await tester.pumpWidget(_app(repo));
    await tester.pumpAndSettle();

    expect(find.byType(PopupMenuButton<String>), findsNothing);
    expect(find.text('إيقاف المركبة'), findsOneWidget);
    expect(find.text('حذف'), findsOneWidget);
  });

  testWidgets('disabling explains the consequences and needs confirmation',
      (tester) async {
    tester.view.physicalSize = const Size(800, 2000);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    final repo = _RecordingVehicles(sampleVehicles);
    await tester.pumpWidget(_app(repo));
    await tester.pumpAndSettle();

    await tester.tap(find.text('إيقاف المركبة'));
    await tester.pumpAndSettle();
    expect(find.textContaining('صفحة "غير متاح"'), findsOneWidget);
    expect(repo.calls, isEmpty, reason: 'nothing happens before confirming');

    await tester.tap(find.text('إلغاء'));
    await tester.pumpAndSettle();
    expect(repo.calls, isEmpty);

    await tester.tap(find.text('إيقاف المركبة'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('إيقاف').last);
    await tester.pumpAndSettle();
    expect(repo.calls, ['status:${repo.items.first.id}:false']);
  });

  testWidgets('delete asks first and only deletes after confirming',
      (tester) async {
    tester.view.physicalSize = const Size(800, 2000);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    final repo = _RecordingVehicles(sampleVehicles);
    await tester.pumpWidget(_app(repo));
    await tester.pumpAndSettle();

    await tester.tap(find.text('حذف'));
    await tester.pumpAndSettle();
    expect(find.text('حذف هذه المركبة؟'), findsOneWidget);
    expect(repo.calls, isEmpty);

    await tester.tap(find.text('إلغاء'));
    await tester.pumpAndSettle();
    expect(repo.calls, isEmpty);

    await tester.tap(find.text('حذف'));
    await tester.pumpAndSettle();
    await tester.tap(find.descendant(
        of: find.byType(AlertDialog), matching: find.text('حذف')));
    await tester.pumpAndSettle();
    expect(repo.calls, contains('delete:${repo.items.first.id}'));
  });

  testWidgets('a disabled vehicle shows a banner and offers reactivation',
      (tester) async {
    tester.view.physicalSize = const Size(800, 2000);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    final first = sampleVehicles.first;
    final disabled = VehicleModel(
      id: first.id,
      plateNumber: first.plateNumber,
      plateType: first.plateType,
      vehicleType: first.vehicleType,
      status: 'inactive',
      tags: first.tags,
    );
    final repo = _RecordingVehicles([disabled]);
    await tester.pumpWidget(_app(repo));
    await tester.pumpAndSettle();

    expect(find.textContaining('هذه المركبة موقوفة'), findsOneWidget);
    expect(find.text('إعادة تفعيل المركبة'), findsOneWidget);
  });
}
