import 'dart:io';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:qreach_smarttag/core/theme/app_colors.dart';
import 'package:qreach_smarttag/core/theme/app_theme.dart';
import 'package:qreach_smarttag/features/contacts/presentation/contacts_screen.dart';
import 'package:qreach_smarttag/features/home/presentation/home_screen.dart';
import 'package:qreach_smarttag/features/profile/presentation/profile_screen.dart';
import 'package:qreach_smarttag/features/shell/app_shell.dart';
import 'package:qreach_smarttag/features/vehicles/presentation/vehicle_list_screen.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

import '../support/preview_support.dart';

/// The real shell (floating navigation bar + tabs), to review the navigation
/// and the tab switch animation.
void main() {
  setUpAll(loadFonts);

  Future<void> shootShell(WidgetTester tester, String name,
      {int tab = 0, bool dark = false, Size size = const Size(390, 844)}) async {
    tester.view.physicalSize = size * 2;
    tester.view.devicePixelRatio = 2;
    addTearDown(tester.view.reset);

    final key = GlobalKey();
    final router = GoRouter(
      initialLocation: '/home',
      routes: [
        StatefulShellRoute.indexedStack(
          builder: (context, state, shell) => AppShell(navigationShell: shell),
          branches: [
            StatefulShellBranch(routes: [
              GoRoute(path: '/home', builder: (_, __) => const HomeScreen())
            ]),
            StatefulShellBranch(routes: [
              GoRoute(
                  path: '/vehicles',
                  builder: (_, __) => const VehicleListScreen())
            ]),
            StatefulShellBranch(routes: [
              GoRoute(
                  path: '/contacts', builder: (_, __) => const ContactsScreen())
            ]),
            StatefulShellBranch(routes: [
              GoRoute(
                  path: '/profile', builder: (_, __) => const ProfileScreen())
            ]),
          ],
        ),
      ],
    );

    await tester.pumpWidget(ProviderScope(
      overrides: previewOverrides(),
      child: RepaintBoundary(
        key: key,
        child: MaterialApp.router(
          debugShowCheckedModeBanner: false,
          theme: AppTheme.build(dark: dark),
          locale: const Locale('ar'),
          supportedLocales: const [Locale('ar'), Locale('en')],
          localizationsDelegates: const [
            AppLocalizations.delegate,
            GlobalMaterialLocalizations.delegate,
            GlobalWidgetsLocalizations.delegate,
            GlobalCupertinoLocalizations.delegate,
          ],
          routerConfig: router,
          builder: (context, child) {
            AppColors.dark = dark;
            return child!;
          },
        ),
      ),
    ));
    await tester.pump(const Duration(milliseconds: 300));

    if (tab > 0) {
      router.go(['/home', '/vehicles', '/contacts', '/profile'][tab]);
      await tester.pump(const Duration(milliseconds: 100));
    }
    await tester.pump(const Duration(milliseconds: 1200));

    final boundary =
        tester.renderObject<RenderRepaintBoundary>(find.byKey(key));
    final bytes = await tester.runAsync(() async {
      final image = await boundary.toImage(pixelRatio: 1);
      final data = await image.toByteData(format: ui.ImageByteFormat.png);
      return data!.buffer.asUint8List();
    });
    Directory('build/previews').createSync(recursive: true);
    File('build/previews/$name.png').writeAsBytesSync(bytes!);
    AppColors.dark = false;
  }

  testWidgets('shell home', (t) => shootShell(t, 'shell_home'));
  testWidgets('shell vehicles', (t) => shootShell(t, 'shell_vehicles', tab: 1));
  testWidgets(
      'shell contacts dark', (t) => shootShell(t, 'shell_contacts_dark', tab: 2, dark: true));
  testWidgets('shell home small',
      (t) => shootShell(t, 'shell_home_small', size: const Size(360, 640)));
}
