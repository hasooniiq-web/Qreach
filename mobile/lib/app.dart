import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/locale/locale_controller.dart';
import 'core/router/app_router.dart';
import 'core/theme/app_colors.dart';
import 'core/theme/app_theme.dart';
import 'core/theme/theme_controller.dart';
import 'l10n/app_localizations.dart';

class QreachApp extends ConsumerStatefulWidget {
  const QreachApp({super.key});

  @override
  ConsumerState<QreachApp> createState() => _QreachAppState();
}

class _QreachAppState extends ConsumerState<QreachApp>
    with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  // The phone switched between light and dark while we follow "system".
  @override
  void didChangePlatformBrightness() => setState(() {});

  @override
  Widget build(BuildContext context) {
    final router = ref.watch(routerProvider);
    final locale = ref.watch(localeProvider);
    final mode = ref.watch(themeModeProvider);

    final dark = switch (mode) {
      ThemeMode.light => false,
      ThemeMode.dark => true,
      ThemeMode.system =>
        WidgetsBinding.instance.platformDispatcher.platformBrightness ==
            Brightness.dark,
    };

    // Colours are read from AppColors while widgets build, so the palette flag
    // must be right before the tree builds; the keyed wrapper below rebuilds
    // everything when the mode flips.
    final theme = AppTheme.build(dark: dark);

    return MaterialApp.router(
      title: 'Qreach SmartTag',
      debugShowCheckedModeBanner: false,
      theme: theme,
      themeMode: ThemeMode.light,
      routerConfig: router,
      locale: locale,
      supportedLocales: LocaleController.supported,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      builder: (context, child) {
        AppColors.dark = dark;
        // Lateef's x-height reads noticeably smaller than Cairo's at the same
        // declared font size, and most screens' text sizes were tuned for
        // Cairo. Boosting on top of whatever text-scale the user already has
        // (rather than replacing it) keeps their own accessibility setting
        // in effect instead of overriding it.
        final mq = MediaQuery.of(context);
        final boosted = TextScaler.linear(mq.textScaler.scale(1.0) * 1.2);
        return KeyedSubtree(
          key: ValueKey(dark),
          child: MediaQuery(
            data: mq.copyWith(textScaler: boosted),
            child: child!,
          ),
        );
      },
    );
  }
}
