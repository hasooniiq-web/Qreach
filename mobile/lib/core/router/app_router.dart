import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/auth_controller.dart';
import '../../features/auth/presentation/otp_screen.dart';
import '../../features/auth/presentation/phone_entry_screen.dart';
import '../../features/contacts/presentation/contact_thread_screen.dart';
import '../../features/contacts/presentation/contacts_screen.dart';
import '../../features/home/presentation/home_screen.dart';
import '../../features/notifications/presentation/notifications_screen.dart';
import '../../features/profile/presentation/profile_screen.dart';
import '../../features/profile/presentation/visibility_screen.dart';
import '../../features/server/server_setup_screen.dart';
import '../../features/shell/app_shell.dart';
import '../../features/subscription/presentation/subscription_screen.dart';
import '../../features/tags/presentation/scan_screen.dart';
import '../../features/vehicles/presentation/vehicle_detail_screen.dart';
import '../../features/vehicles/presentation/vehicle_form_screen.dart';
import '../../features/vehicles/presentation/vehicle_list_screen.dart';
import '../design/page_transitions.dart';
import '../splash_screen.dart';

/// Rebuilding the whole router on auth-state change is a deliberate
/// simplification: login/logout are exactly the moments where resetting
/// the navigation stack is the *correct* behavior anyway (you never want
/// "back" to return you to a screen you were on before logging out).
///
/// Structure: four bottom tabs (home, vehicles, contacts, profile) in a
/// shell; every other screen is a top-level route that opens full-screen
/// over the tabs.
final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authControllerProvider);

  return GoRouter(
    initialLocation: '/',
    redirect: (context, state) {
      final loggingIn = state.matchedLocation.startsWith('/login');

      if (authState is AuthUnknown) return '/';
      final onServer = state.matchedLocation == '/server';
      if (authState is AuthUnauthenticated) {
        return (loggingIn || onServer) ? null : '/login';
      }
      if (authState is AuthAuthenticated &&
          (loggingIn || state.matchedLocation == '/')) {
        return '/home';
      }
      return null;
    },
    routes: [
      GoRoute(path: '/', builder: (context, state) => const SplashScreen()),
      GoRoute(
        path: '/login',
        pageBuilder: animatedPage((context, state) => const PhoneEntryScreen()),
      ),
      GoRoute(
        path: '/login/otp',
        pageBuilder: animatedPage(
            (context, state) => OtpScreen(phone: state.extra as String? ?? '')),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) =>
            AppShell(navigationShell: navigationShell),
        branches: [
          StatefulShellBranch(routes: [
            GoRoute(
                path: '/home', builder: (context, state) => const HomeScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(
                path: '/vehicles',
                builder: (context, state) => const VehicleListScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(
                path: '/contacts',
                builder: (context, state) => const ContactsScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(
                path: '/profile',
                builder: (context, state) => const ProfileScreen()),
          ]),
        ],
      ),
      GoRoute(
        path: '/contacts/:id',
        pageBuilder: animatedPage((context, state) =>
            ContactThreadScreen(requestId: state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/notifications',
        pageBuilder:
            animatedPage((context, state) => const NotificationsScreen()),
      ),
      GoRoute(
        path: '/vehicles/new',
        pageBuilder:
            animatedPage((context, state) => const VehicleFormScreen()),
      ),
      GoRoute(
        path: '/vehicles/:id',
        pageBuilder: animatedPage((context, state) =>
            VehicleDetailScreen(vehicleId: state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/vehicles/:id/scan',
        pageBuilder: animatedPage((context, state) =>
            ScanScreen(vehicleId: state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/subscription',
        pageBuilder:
            animatedPage((context, state) => const SubscriptionScreen()),
      ),
      GoRoute(
        path: '/server',
        pageBuilder:
            animatedPage((context, state) => const ServerSetupScreen()),
      ),
      GoRoute(
        path: '/visibility',
        pageBuilder: animatedPage((context, state) => const VisibilityScreen()),
      ),
    ],
  );
});
