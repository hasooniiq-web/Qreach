import 'package:flutter/material.dart';

/// SmartTag brand palette — only these three colors, per the product spec.
/// Every other color used in the app must be a neutral/status shade derived
/// here, never a new hue introduced ad-hoc in a screen.
abstract final class AppColors {
  static const Color brandYellow = Color(0xFFFFC107);
  static const Color brandBlack = Color(0xFF000000);
  static const Color brandWhite = Color(0xFFFFFFFF);

  static const Color ink900 = Color(0xFF0A0A0A);
  static const Color ink800 = Color(0xFF171717);
  static const Color ink700 = Color(0xFF262626);
  static const Color ink600 = Color(0xFF404040);
  static const Color ink500 = Color(0xFF595959);
  static const Color ink400 = Color(0xFF737373);
  static const Color ink300 = Color(0xFFA3A3A3);
  static const Color ink200 = Color(0xFFD4D4D4);
  static const Color ink100 = Color(0xFFE8E8E8);
  static const Color ink50 = Color(0xFFF5F5F5);

  static const Color statusSuccess = Color(0xFF1E7A34);
  static const Color statusDanger = Color(0xFFB3261E);
  static const Color statusWarning = Color(0xFFB7791B);
}
