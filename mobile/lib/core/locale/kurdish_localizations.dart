import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

/// Flutter ships no built-in strings for Central Kurdish (Sorani, `ckb`) for its
/// own widgets (date pickers, "OK/Cancel" in system dialogs, text-field menus).
/// Those few framework strings fall back to Arabic — the nearest language that is
/// also right-to-left and Arabic-script — while all of the app's own text comes
/// from `app_ckb.arb`.
const _kurdish = 'ckb';
const _fallback = Locale('ar');

class KurdishMaterialDelegate extends LocalizationsDelegate<MaterialLocalizations> {
  const KurdishMaterialDelegate();

  @override
  bool isSupported(Locale locale) => locale.languageCode == _kurdish;

  @override
  Future<MaterialLocalizations> load(Locale locale) =>
      GlobalMaterialLocalizations.delegate.load(_fallback);

  @override
  bool shouldReload(KurdishMaterialDelegate old) => false;
}

class KurdishCupertinoDelegate extends LocalizationsDelegate<CupertinoLocalizations> {
  const KurdishCupertinoDelegate();

  @override
  bool isSupported(Locale locale) => locale.languageCode == _kurdish;

  @override
  Future<CupertinoLocalizations> load(Locale locale) =>
      GlobalCupertinoLocalizations.delegate.load(_fallback);

  @override
  bool shouldReload(KurdishCupertinoDelegate old) => false;
}

/// Right-to-left text direction (and the few widget strings) for Kurdish.
class KurdishWidgetsDelegate extends LocalizationsDelegate<WidgetsLocalizations> {
  const KurdishWidgetsDelegate();

  @override
  bool isSupported(Locale locale) => locale.languageCode == _kurdish;

  @override
  Future<WidgetsLocalizations> load(Locale locale) =>
      GlobalWidgetsLocalizations.delegate.load(_fallback);

  @override
  bool shouldReload(KurdishWidgetsDelegate old) => false;
}

const kurdishLocalizationDelegates = <LocalizationsDelegate<dynamic>>[
  KurdishMaterialDelegate(),
  KurdishCupertinoDelegate(),
  KurdishWidgetsDelegate(),
];
