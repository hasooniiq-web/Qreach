import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers.dart';

/// The app language: Arabic (default) or English. The choice is remembered
/// on the device, and flipping it also flips the text direction (RTL/LTR).
class LocaleController extends Notifier<Locale> {
  static const supported = [Locale('ar'), Locale('en')];

  @override
  Locale build() {
    _restore();
    return const Locale('ar');
  }

  Future<void> _restore() async {
    try {
      final saved = await ref.read(secureStorageProvider).readLocale();
      if (saved == 'en' || saved == 'ar') state = Locale(saved!);
    } catch (_) {
      // Storage can be unavailable; Arabic stays.
    }
  }

  Future<void> setLocale(Locale locale) async {
    state = locale;
    try {
      await ref.read(secureStorageProvider).saveLocale(locale.languageCode);
    } catch (_) {}
  }
}

final localeProvider =
    NotifierProvider<LocaleController, Locale>(LocaleController.new);
