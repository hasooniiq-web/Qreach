import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Corner radii. Three steps only: small controls, cards/sheets, hero surfaces.
abstract final class AppRadius {
  static const double sm = 12;
  static const double md = 18;
  static const double lg = 26;
  static const double pill = 999;

  static BorderRadius get rSm => BorderRadius.circular(sm);
  static BorderRadius get rMd => BorderRadius.circular(md);
  static BorderRadius get rLg => BorderRadius.circular(lg);
}

/// Elevation is expressed as soft, low-contrast shadows in light mode and as a
/// hairline border in dark mode (shadows are invisible on dark surfaces).
abstract final class AppShadows {
  static List<BoxShadow> get card => AppColors.dark
      ? const []
      : const [
          BoxShadow(
              color: Color(0x0F000000), blurRadius: 18, offset: Offset(0, 6)),
          BoxShadow(
              color: Color(0x08000000), blurRadius: 2, offset: Offset(0, 1)),
        ];

  static List<BoxShadow> get raised => AppColors.dark
      ? const []
      : const [
          BoxShadow(
              color: Color(0x1A000000), blurRadius: 28, offset: Offset(0, 12)),
        ];

  static Border? get hairline =>
      AppColors.dark ? Border.all(color: AppColors.ink200) : null;
}

/// One motion vocabulary. Fast for feedback, medium for entrances, slow only
/// for large shared-element moves.
abstract final class AppMotion {
  static const Duration fast = Duration(milliseconds: 160);
  static const Duration base = Duration(milliseconds: 280);
  static const Duration slow = Duration(milliseconds: 420);

  /// Things arriving on screen decelerate.
  static const Curve enter = Curves.easeOutCubic;

  /// Things that respond to a touch settle with a touch of overshoot.
  static const Curve settle = Curves.easeOutBack;

  /// Things that change in place.
  static const Curve standard = Curves.easeInOutCubic;

  /// The delay between siblings in a staggered entrance.
  static const Duration stagger = Duration(milliseconds: 55);
}

/// Type scale on top of Cairo. Screens ask for a role, not a size.
abstract final class AppText {
  static TextStyle display(BuildContext context) => TextStyle(
        fontSize: 30,
        height: 1.2,
        fontWeight: FontWeight.w800,
        color: AppColors.ink900,
      );

  static TextStyle title(BuildContext context) => TextStyle(
        fontSize: 20,
        height: 1.3,
        fontWeight: FontWeight.w800,
        color: AppColors.ink900,
      );

  static TextStyle section(BuildContext context) => TextStyle(
        fontSize: 16,
        height: 1.3,
        fontWeight: FontWeight.w700,
        color: AppColors.ink900,
      );

  static TextStyle body(BuildContext context) => TextStyle(
        fontSize: 14,
        height: 1.55,
        fontWeight: FontWeight.w500,
        color: AppColors.ink700,
      );

  static TextStyle caption(BuildContext context) => TextStyle(
        fontSize: 12,
        height: 1.4,
        fontWeight: FontWeight.w500,
        color: AppColors.ink500,
      );
}
