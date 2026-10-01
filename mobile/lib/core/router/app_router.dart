import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/auth_choice_screen.dart';
import '../../features/auth/presentation/auth_controller.dart';
import '../../features/auth/presentation/forgot_password_screen.dart';
import '../../features/auth/presentation/lock_screen.dart';
import '../../features/auth/presentation/login_screen.dart';
import '../../features/auth/presentation/otp_screen.dart';
import '../../features/auth/presentation/signup_screen.dart';
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
      final onLock = state.matchedLocation == '/lock';

      if (authState is AuthUnknown) return '/';
      final onServer = state.matchedLocation == '/server';
      if (authState is AuthUnauthenticated) {
        return (loggingIn || onServer) ? null : '/login';
      }
      if (authState is AuthLocked) {
        return onLock ? null : '/lock';
      }
      if (authState is AuthAuthenticated &&
          (loggingIn || onLock || state.matchedLocation == '/')) {
        return '/home';
      }
      return null;
    },
    routes: [
      GoRoute(path: '/', builder: (context, state) => const SplashScreen()),
      GoRoute(
        path: '/login',
        pageBuilder: animatedPage((context, state) => const AuthChoiceScreen()),
      ),
      GoRoute(
        path: '/login/signup',
        pageBuilder: animatedPage((context, state) => const SignupScreen()),
      ),
      GoRoute(
        path: '/login/signup/otp',
        pageBuilder: animatedPage((context, state) {
          final extra =
              state.extra as ({String name, String phone, String password});
          return OtpScreen(
              name: extra.name, phone: extra.phone, password: extra.password);
        }),
      ),
      GoRoute(
        path: '/login/password',
        pageBuilder: animatedPage((context, state) => const LoginScreen()),
      ),
      GoRoute(
        path: '/login/password/forgot',
        pageBuilder:
            animatedPage((context, state) => const ForgotPasswordScreen()),
      ),
      GoRoute(
        path: '/lock',
        pageBuilder: animatedPage((context, state) => const LockScreen()),
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
