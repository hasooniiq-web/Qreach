import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/features/vehicles/data/car_catalog.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';
import 'package:qreach_smarttag/shared/widgets/choice_field.dart';

Widget _host(Widget child) => MaterialApp(
      locale: const Locale('ar'),
      supportedLocales: const [Locale('ar'), Locale('en')],
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: Scaffold(
          body: Padding(padding: const EdgeInsets.all(16), child: child)),
    );

void main() {
  testWidgets('picking an option reports it and shows its label',
      (tester) async {
    String? picked;
    await tester.pumpWidget(_host(StatefulBuilder(
      builder: (context, setState) => ChoiceField(
        label: 'اللون',
        value: picked,
        options: const [
          ChoiceOption(value: 'أبيض', label: 'أبيض'),
          ChoiceOption(value: 'أسود', label: 'أسود'),
        ],
        onChanged: (v) => setState(() => picked = v),
      ),
    )));

    await tester.tap(find.text('اللون'));
    await tester.pumpAndSettle();
    expect(find.text('أبيض'), findsOneWidget);
    expect(find.text('أخرى'), findsOneWidget);

    await tester.tap(find.text('أسود'));
    await tester.pumpAndSettle();
    expect(picked, 'أسود');
  });

  testWidgets('Other is a plain choice: nothing is typed, the value is "أخرى"',
      (tester) async {
    String? picked;
    await tester.pumpWidget(_host(StatefulBuilder(
      builder: (context, setState) => ChoiceField(
        label: 'الشركة المصنعة',
        value: picked,
        options: const [ChoiceOption(value: 'تويوتا', label: 'تويوتا')],
        onChanged: (v) => setState(() => picked = v),
      ),
    )));

    await tester.tap(find.text('الشركة المصنعة'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('أخرى'));
    await tester.pumpAndSettle();

    // No text box is opened — the sheet closes with the value set.
    expect(find.byType(TextField), findsNothing);
    expect(picked, 'أخرى');
  });

  testWidgets('without Other (governorate) there is no free text option',
      (tester) async {
    await tester.pumpWidget(_host(ChoiceField(
      label: 'المحافظة',
      value: null,
      allowOther: false,
      options: const [ChoiceOption(value: 'بغداد', label: 'بغداد')],
      onChanged: (_) {},
    )));

    await tester.tap(find.text('المحافظة'));
    await tester.pumpAndSettle();
    expect(find.text('بغداد'), findsOneWidget);
    expect(find.text('أخرى'), findsNothing);
  });

  test('the car catalog finds a make by either language', () {
    final catalog = CarCatalog.fromJson(
        '{"makes":[{"en":"Toyota","ar":"تويوتا","models":["Corolla","Camry"]}]}');
    expect(catalog.find('Toyota')!.models, contains('Camry'));
    expect(catalog.find('تويوتا')!.en, 'Toyota');
    expect(catalog.find('Unknown'), isNull);
  });
}
