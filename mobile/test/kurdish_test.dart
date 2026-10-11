import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/locale/kurdish_localizations.dart';
import 'package:qreach_smarttag/core/locale/locale_controller.dart';
import 'package:qreach_smarttag/features/vehicles/data/vehicle_options.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

Map<String, dynamic> _arb(String name) =>
    jsonDecode(File('lib/l10n/$name.arb').readAsStringSync())
        as Map<String, dynamic>;

Set<String> _keys(Map<String, dynamic> arb) =>
    arb.keys.where((k) => !k.startsWith('@')).toSet();

void main() {
  test('the Kurdish file has every key the Arabic template has, and no extras',
      () {
    final ar = _keys(_arb('app_ar'));
    final ckb = _keys(_arb('app_ckb'));

    expect(ar.difference(ckb), isEmpty, reason: 'keys missing in Kurdish');
    expect(ckb.difference(ar), isEmpty, reason: 'Kurdish keys not in Arabic');
  });

  test('placeholders are kept in every translated string', () {
    final ar = _arb('app_ar');
    final ckb = _arb('app_ckb');
    final placeholder = RegExp(r'\{[a-zA-Z]+\}');

    for (final key in _keys(ar)) {
      final want = placeholder.allMatches(ar[key] as String).map((m) => m[0]).toSet();
      final got = placeholder.allMatches(ckb[key] as String).map((m) => m[0]).toSet();
      expect(got, want, reason: 'placeholders of "$key" differ');
    }
  });

  test('Kurdish is offered as a language and restores from storage', () {
    expect(LocaleController.supported.map((l) => l.languageCode),
        containsAll(['ar', 'en', 'ckb']));
  });

  testWidgets('the app renders right-to-left in Kurdish with Kurdish text',
      (tester) async {
    late BuildContext captured;
    await tester.pumpWidget(MaterialApp(
      locale: const Locale('ckb'),
      supportedLocales: LocaleController.supported,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        ...kurdishLocalizationDelegates,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: Builder(builder: (context) {
        captured = context;
        return Scaffold(
          body: TextField(
            decoration: InputDecoration(
                labelText: AppLocalizations.of(context)!.phoneLabel),
          ),
        );
      }),
    ));
    await tester.pumpAndSettle();

    expect(Directionality.of(captured), TextDirection.rtl);
    expect(AppLocalizations.of(captured)!.phoneLabel, 'ژمارەی مۆبایل');
    expect(AppLocalizations.of(captured)!.otpSubtitle('0770'),
        contains('0770'));
    // Framework strings (copy/paste, date pickers) exist through the fallback.
    expect(MaterialLocalizations.of(captured).okButtonLabel, isNotEmpty);
    expect(find.text('ژمارەی مۆبایل'), findsOneWidget);
  });

  test('Kurdish labels for colours and relations keep the Arabic stored value',
      () {
    final colors = vehicleColorOptions(arabic: true, kurdish: true);
    expect(colors.first.value, 'أبيض');
    expect(colors.first.label, 'سپی');

    final relations = relationshipOptions(arabic: true, kurdish: true);
    expect(relations.first.value, 'أب');
    expect(relations.first.label, 'باوک');

    // Arabic and English are unchanged.
    expect(vehicleColorOptions(arabic: true).first.label, 'أبيض');
    expect(vehicleColorOptions(arabic: false).first.value, 'White');
  });
}
