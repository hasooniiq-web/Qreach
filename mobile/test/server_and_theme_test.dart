import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/theme/app_colors.dart';
import 'package:qreach_smarttag/core/theme/app_theme.dart';
import 'package:qreach_smarttag/features/server/server_setup_screen.dart';

void main() {
  group('parseServerAddress', () {
    test('a LAN ip with a port is plain http', () {
      expect(parseServerAddress('192.168.68.103:8001'),
          'http://192.168.68.103:8001/api/v1');
    });

    test('a bare domain is https, a full url keeps its scheme', () {
      expect(parseServerAddress('qreach.example.com'),
          'https://qreach.example.com/api/v1');
      expect(parseServerAddress('http://10.0.2.2:8000'),
          'http://10.0.2.2:8000/api/v1');
      expect(parseServerAddress('https://api.qreach.app/api/v1'),
          'https://api.qreach.app/api/v1');
    });

    test('rejects blanks, spaces and impossible ports', () {
      expect(parseServerAddress(''), isNull);
      expect(parseServerAddress('my server:80'), isNull);
      expect(parseServerAddress('10.0.0.1:99999'), isNull);
    });
  });

  test('dark and light palettes differ and share the brand colours', () {
    final light = AppTheme.build(dark: false);
    final lightInk = AppColors.ink900;
    final lightSurface = AppColors.surface;
    final dark = AppTheme.build(dark: true);

    expect(light.brightness, Brightness.light);
    expect(dark.brightness, Brightness.dark);
    expect(AppColors.ink900, isNot(lightInk));
    expect(AppColors.surface, isNot(lightSurface));
    expect(AppColors.brandYellow, const Color(0xFFFFC107));
    expect(dark.textTheme.bodyMedium?.fontFamily, 'Lateef');

    AppColors.dark = false; // leave global state clean for other tests
  });
}
