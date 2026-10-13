import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/core/utils/phone_input.dart';
import 'package:qreach_smarttag/features/tags/data/tag_model.dart';
import 'package:qreach_smarttag/features/vehicles/data/vehicle_model.dart';
import 'package:qreach_smarttag/features/vehicles/presentation/vehicle_detail_screen.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

import 'support/preview_support.dart';

class _Recording extends FakeVehicles {
  _Recording(super.items);

  final List<String> calls = [];

  @override
  Future<void> relinkTag(String vehicleId) async => calls.add('relink:$vehicleId');

  @override
  Future<void> setChannels(String vehicleId,
          {required bool qr, required bool nfc}) async =>
      calls.add('channels:qr=$qr,nfc=$nfc');
}

const _assigned = TagModel(
  token: 'TOKEN1',
  serialNumber: 'ST-C26-8K7M2P-4',
  type: 'code',
  status: 'assigned',
  publicUrl: 'https://qreach.online/t/TOKEN1',
);

Widget _app(_Recording repo) => ProviderScope(
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
  group('a vehicle with no code', () {
    testWidgets('offers only linking: no edit, pause or delete', (tester) async {
      _tall(tester);
      final repo = _Recording([
        const VehicleModel(id: 'v-1', plateNumber: '65600', vehicleType: 'sedan'),
      ]);
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      expect(find.text('ربط رمز جديد'), findsOneWidget);
      expect(find.text('تعديل'), findsNothing);
      expect(find.text('إيقاف المركبة'), findsNothing);
      expect(find.text('حذف'), findsNothing);
      // Nothing was removed before, so there is no "link it again" button either.
      expect(find.textContaining('إعادة ربط'), findsNothing);
    });

    testWidgets('offers the code that was removed last and links it back',
        (tester) async {
      _tall(tester);
      final repo = _Recording([
        const VehicleModel(
            id: 'v-1',
            plateNumber: '65600',
            vehicleType: 'sedan',
            previousTagSerial: 'ST-C26-8K7M2P-4'),
      ]);
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      expect(find.text('ربط رمز جديد'), findsOneWidget);
      expect(find.textContaining('إعادة ربط آخر رمز محذوف'), findsOneWidget);
      expect(find.textContaining('ST-C26-8K7M2P-4'), findsWidgets);

      await tester.tap(find.textContaining('إعادة ربط آخر رمز محذوف'));
      await tester.pumpAndSettle();

      expect(repo.calls, ['relink:v-1']);
    });
  });

  group('a vehicle with a code', () {
    testWidgets('shows the QR and NFC switches and the vehicle actions',
        (tester) async {
      _tall(tester);
      final repo = _Recording([
        const VehicleModel(
            id: 'v-1', plateNumber: '65600', vehicleType: 'sedan', tags: [_assigned]),
      ]);
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      expect(find.text('طرق فتح الرمز'), findsOneWidget);
      expect(find.text('رمز QR المطبوع'), findsOneWidget);
      expect(find.text('شريحة NFC'), findsOneWidget);
      expect(find.text('تعديل'), findsOneWidget);
      expect(find.text('إيقاف المركبة'), findsOneWidget);
    });

    testWidgets('switching QR off keeps NFC on', (tester) async {
      _tall(tester);
      final repo = _Recording([
        const VehicleModel(
            id: 'v-1', plateNumber: '65600', vehicleType: 'sedan', tags: [_assigned]),
      ]);
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      await tester.tap(find.widgetWithText(SwitchListTile, 'رمز QR المطبوع'));
      await tester.pumpAndSettle();

      expect(repo.calls, ['channels:qr=false,nfc=true']);
    });

    testWidgets('both switched off says nobody can open the code',
        (tester) async {
      _tall(tester);
      final repo = _Recording([
        VehicleModel(
          id: 'v-1',
          plateNumber: '65600',
          vehicleType: 'sedan',
          tags: [
            TagModel(
              token: 'T',
              serialNumber: _assigned.serialNumber,
              type: 'code',
              status: 'assigned',
              publicUrl: _assigned.publicUrl,
              qrEnabled: false,
              nfcEnabled: false,
            ),
          ],
        ),
      ]);
      await tester.pumpWidget(_app(repo));
      await tester.pumpAndSettle();

      expect(find.textContaining('الطريقتان موقوفتان'), findsOneWidget);
    });
  });

  group('phone fields', () {
    TextEditingValue type(String text) {
      var value = TextEditingValue.empty;
      for (final f in localPhoneFormatters) {
        value = f.formatEditUpdate(TextEditingValue.empty, TextEditingValue(text: text));
        text = value.text;
      }
      return value;
    }

    test('10 digits without the zero, 11 with it, nothing longer', () {
      expect(type('7701234567').text, '7701234567');
      expect(type('07701234567').text, '07701234567');
    });

    test('Arabic-Indic digits are converted and letters dropped', () {
      expect(type('٠٧٧٠١٢٣٤٥٦٧').text, '07701234567');
      expect(type('77a01 234-567').text, '7701234567');
    });

    test('an eleventh digit is refused without a zero, a twelfth with one', () {
      const formatter = _Chain();
      expect(formatter.apply('7701234567', add: '8'), '7701234567');
      expect(formatter.apply('07701234567', add: '8'), '07701234567');
    });
  });
}

/// Types one more character after [text] through the whole formatter chain.
class _Chain {
  const _Chain();

  String apply(String text, {required String add}) {
    var old = TextEditingValue(text: text);
    var value = TextEditingValue(text: text + add);
    for (final TextInputFormatter f in localPhoneFormatters) {
      value = f.formatEditUpdate(old, value);
    }
    return value.text;
  }
}
