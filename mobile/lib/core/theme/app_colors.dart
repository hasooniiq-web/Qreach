import 'package:flutter/material.dart';

/// SmartTag brand palette plus neutral shades for both light and dark mode.
///
/// The brand colours (yellow, black, white) never change. The neutral "ink"
/// scale is flipped in dark mode (ink900 is the strongest text colour in both
/// modes: near-black on light, near-white on dark; ink50 is the faintest fill),
/// so widgets keep using the same names and simply follow the mode.
/// [surface] is the colour of cards and sheets, [onSurface] the colour of
/// content drawn directly on it.
///
/// [dark] is set once per frame by the app root before anything paints (see
/// `QreachApp`), and the whole tree is rebuilt when it changes.
abstract final class AppColors {
  static bool dark = false;

  // Brand — identical in both modes.
  static const Color brandYellow = Color(0xFFFFC107);
  static const Color brandBlack = Color(0xFF000000);
  static const Color brandWhite = Color(0xFFFFFFFF);

  static Color get surface =>
      dark ? const Color(0xFF1E1E21) : const Color(0xFFFFFFFF);
  static Color get onSurface =>
      dark ? const Color(0xFFFFFFFF) : const Color(0xFF000000);
  static Color get background =>
      dark ? const Color(0xFF121214) : const Color(0xFFF5F5F5);

  static Color get ink900 =>
      dark ? const Color(0xFFF2F2F2) : const Color(0xFF0A0A0A);
  static Color get ink800 =>
      dark ? const Color(0xFFE4E4E4) : const Color(0xFF171717);
  static Color get ink700 =>
      dark ? const Color(0xFFD0D0D0) : const Color(0xFF262626);
  static Color get ink600 =>
      dark ? const Color(0xFFB8B8B8) : const Color(0xFF404040);
  static Color get ink500 =>
      dark ? const Color(0xFF9E9E9E) : const Color(0xFF595959);
  static Color get ink400 =>
      dark ? const Color(0xFF858585) : const Color(0xFF737373);
  static Color get ink300 =>
      dark ? const Color(0xFF626262) : const Color(0xFFA3A3A3);
  static Color get ink200 =>
      dark ? const Color(0xFF3B3B40) : const Color(0xFFD4D4D4);
  static Color get ink100 =>
      dark ? const Color(0xFF2A2A2E) : const Color(0xFFE8E8E8);
  static Color get ink50 =>
      dark ? const Color(0xFF232326) : const Color(0xFFF5F5F5);

  static Color get statusSuccess =>
      dark ? const Color(0xFF4CAF62) : const Color(0xFF1E7A34);
  static Color get statusDanger =>
      dark ? const Color(0xFFEF6C62) : const Color(0xFFB3261E);
  static Color get statusWarning =>
      dark ? const Color(0xFFE0A63A) : const Color(0xFFB7791B);
}
