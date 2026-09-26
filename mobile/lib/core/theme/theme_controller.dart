import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers.dart';

/// Appearance: follow the phone (default), or force light or dark. The choice
/// is remembered on the device.
class ThemeModeController extends Notifier<ThemeMode> {
  static const _key = 'theme_mode';

  @override
  ThemeMode build() {
    _restore();
    return ThemeMode.system;
  }

  Future<void> _restore() async {
    try {
      final saved = await ref.read(secureStorageProvider).readValue(_key);
      final mode = ThemeMode.values.where((m) => m.name == saved).firstOrNull;
      if (mode != null) state = mode;
    } catch (_) {
      // Storage unavailable: stay on "system".
    }
  }

  Future<void> setMode(ThemeMode mode) async {
    state = mode;
    try {
      await ref.read(secureStorageProvider).writeValue(_key, mode.name);
    } catch (_) {}
  }
}

final themeModeProvider =
    NotifierProvider<ThemeModeController, ThemeMode>(ThemeModeController.new);
