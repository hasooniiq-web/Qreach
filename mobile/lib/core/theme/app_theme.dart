import 'package:flutter/material.dart';

import '../design/tokens.dart';
import 'app_colors.dart';

/// Cairo throughout (bundled in assets/fonts, so it works offline), in light
/// and dark. Building a theme also sets [AppColors.dark] so every widget that
/// reads the palette agrees with the theme being built.
abstract final class AppTheme {
  static const fontFamily = 'Cairo';

  static ThemeData build({required bool dark}) {
    AppColors.dark = dark;
    final brightness = dark ? Brightness.dark : Brightness.light;

    final scheme = ColorScheme.fromSeed(
      seedColor: AppColors.brandYellow,
      brightness: brightness,
      // On light, black is the primary; on dark the yellow takes over and the
      // text on it stays black.
      primary: dark ? AppColors.brandYellow : AppColors.brandBlack,
      onPrimary: dark ? AppColors.brandBlack : AppColors.brandWhite,
      secondary: AppColors.brandYellow,
      onSecondary: AppColors.brandBlack,
      surface: AppColors.surface,
      onSurface: AppColors.ink900,
      error: AppColors.statusDanger,
    );

    final base = ThemeData(
      useMaterial3: true,
      splashFactory: InkSparkle.splashFactory,
      brightness: brightness,
      fontFamily: fontFamily,
      colorScheme: scheme,
      scaffoldBackgroundColor: AppColors.background,
    );

    final textTheme = base.textTheme.apply(
      fontFamily: fontFamily,
      bodyColor: AppColors.ink900,
      displayColor: AppColors.ink900,
    );

    return base.copyWith(
      textTheme: textTheme,
      primaryTextTheme: textTheme,
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.background,
        foregroundColor: AppColors.ink900,
        elevation: 0,
        scrolledUnderElevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: false,
        titleTextStyle: textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w800, fontSize: 18, color: AppColors.ink900),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: dark ? AppColors.brandYellow : AppColors.brandBlack,
          foregroundColor: dark ? AppColors.brandBlack : AppColors.brandWhite,
          minimumSize: const Size.fromHeight(52),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppRadius.sm)),
          textStyle:
              textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w600),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(52),
          foregroundColor: AppColors.ink900,
          side: BorderSide(color: AppColors.ink200),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppRadius.sm)),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
            foregroundColor: dark ? AppColors.brandYellow : AppColors.ink900),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surface,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(color: AppColors.ink200),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(color: AppColors.ink200),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: const BorderSide(color: AppColors.brandYellow, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(color: AppColors.statusDanger),
        ),
        labelStyle: TextStyle(color: AppColors.ink500),
        hintStyle: TextStyle(color: AppColors.ink400),
      ),
      cardTheme: CardThemeData(
        color: AppColors.surface,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: const BorderRadius.all(Radius.circular(12)),
          side: BorderSide(color: AppColors.ink200),
        ),
        margin: EdgeInsets.zero,
      ),
      dividerTheme: DividerThemeData(color: AppColors.ink100, thickness: 1),
      dialogTheme: DialogThemeData(
        shape: RoundedRectangleBorder(borderRadius: AppRadius.rLg),
        backgroundColor: AppColors.surface,
        surfaceTintColor: Colors.transparent,
      ),
      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: AppColors.surface,
        surfaceTintColor: Colors.transparent,
      ),
      chipTheme: ChipThemeData(
        backgroundColor: AppColors.surface,
        selectedColor: dark ? AppColors.brandYellow : AppColors.brandBlack,
        side: BorderSide(color: AppColors.ink200),
        labelStyle: TextStyle(color: AppColors.ink900, fontFamily: fontFamily),
        secondaryLabelStyle: TextStyle(
            color: dark ? AppColors.brandBlack : AppColors.brandWhite,
            fontFamily: fontFamily),
        checkmarkColor: dark ? AppColors.brandBlack : AppColors.brandWhite,
      ),
      switchTheme: SwitchThemeData(
        thumbColor: WidgetStateProperty.resolveWith((s) =>
            s.contains(WidgetState.selected)
                ? AppColors.brandBlack
                : AppColors.ink400),
        trackColor: WidgetStateProperty.resolveWith((s) =>
            s.contains(WidgetState.selected)
                ? AppColors.brandYellow
                : AppColors.ink100),
        trackOutlineColor:
            WidgetStateProperty.all(dark ? AppColors.ink200 : AppColors.ink200),
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: dark ? AppColors.ink100 : AppColors.ink900,
        contentTextStyle: textTheme.bodyMedium
            ?.copyWith(color: dark ? AppColors.ink900 : AppColors.brandWhite),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
    );
  }
}
