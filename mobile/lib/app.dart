import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/locale/kurdish_localizations.dart';
import 'core/locale/locale_controller.dart';
import 'core/providers.dart';
import 'features/auth/presentation/auth_controller.dart';
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
    WidgetsBinding.instance.addPostFrameCallback((_) => _listenForPushTaps());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  // Opens the screen a tapped notification points at. A tap that cold-starts
  // the app arrives before the session is restored, so wait (briefly) for it.
  void _listenForPushTaps() {
    ref.read(pushNotificationServiceProvider).listenForTaps((route) async {
      for (var i = 0; i < 50; i++) {
        if (!mounted) return;
        if (ref.read(authControllerProvider) is AuthAuthenticated) {
          ref.read(routerProvider).push(route);
          return;
        }
        await Future.delayed(const Duration(milliseconds: 100));
      }
    });
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
        ...kurdishLocalizationDelegates,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      builder: (context, child) {
        AppColors.dark = dark;
        return KeyedSubtree(key: ValueKey(dark), child: child!);
      },
    );
  }
}
