import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/auth_controller.dart';
import '../../features/auth/presentation/otp_screen.dart';
import '../../features/auth/presentation/phone_entry_screen.dart';
import '../../features/home/presentation/home_screen.dart';
import '../../features/notifications/presentation/notifications_screen.dart';
import '../../features/profile/presentation/profile_screen.dart';
import '../../features/subscription/presentation/public_contact_screen.dart';
import '../../features/subscription/presentation/subscription_screen.dart';
import '../../features/vehicles/presentation/vehicle_detail_screen.dart';
import '../../features/vehicles/presentation/vehicle_form_screen.dart';
import '../../features/vehicles/presentation/vehicle_list_screen.dart';
import '../../features/tags/presentation/scan_screen.dart';
import '../splash_screen.dart';

/// Rebuilding the whole router on auth-state change is a deliberate
/// simplification: login/logout are exactly the moments where resetting
/// the navigation stack is the *correct* behavior anyway (you never want
/// "back" to return you to a screen you were on before logging out).
final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authControllerProvider);

  return GoRouter(
    initialLocation: '/',
    redirect: (context, state) {
      final loggingIn = state.matchedLocation.startsWith('/login');

      if (authState is AuthUnknown) return '/';
      if (authState is AuthUnauthenticated) return loggingIn ? null : '/login';
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
          builder: (context, state) => const PhoneEntryScreen()),
      GoRoute(
        path: '/login/otp',
        builder: (context, state) =>
            OtpScreen(phone: state.extra as String? ?? ''),
      ),
      GoRoute(path: '/home', builder: (context, state) => const HomeScreen()),
      GoRoute(
          path: '/notifications',
          builder: (context, state) => const NotificationsScreen()),
      GoRoute(
          path: '/vehicles',
          builder: (context, state) => const VehicleListScreen()),
      GoRoute(
          path: '/vehicles/new',
          builder: (context, state) => const VehicleFormScreen()),
      GoRoute(
        path: '/vehicles/:id',
        builder: (context, state) =>
            VehicleDetailScreen(vehicleId: state.pathParameters['id']!),
      ),
      GoRoute(
        path: '/vehicles/:id/scan',
        builder: (context, state) =>
            ScanScreen(vehicleId: state.pathParameters['id']!),
      ),
      GoRoute(
          path: '/profile', builder: (context, state) => const ProfileScreen()),
      GoRoute(
          path: '/subscription',
          builder: (context, state) => const SubscriptionScreen()),
      GoRoute(
          path: '/public-contact',
          builder: (context, state) => const PublicContactScreen()),
    ],
  );
});
