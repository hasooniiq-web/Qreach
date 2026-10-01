import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/splash_screen.dart';
import 'package:qreach_smarttag/features/auth/presentation/auth_choice_screen.dart';
import 'package:qreach_smarttag/features/auth/presentation/forgot_password_screen.dart';
import 'package:qreach_smarttag/features/auth/presentation/login_screen.dart';
import 'package:qreach_smarttag/features/auth/presentation/otp_screen.dart';
import 'package:qreach_smarttag/features/auth/presentation/signup_screen.dart';
import 'package:qreach_smarttag/features/contacts/presentation/contact_thread_screen.dart';
import 'package:qreach_smarttag/features/notifications/presentation/notifications_screen.dart';
import 'package:qreach_smarttag/features/profile/presentation/visibility_screen.dart';
import 'package:qreach_smarttag/features/server/server_setup_screen.dart';
import 'package:qreach_smarttag/features/subscription/presentation/subscription_screen.dart';
import 'package:qreach_smarttag/features/vehicles/presentation/vehicle_detail_screen.dart';
import 'package:qreach_smarttag/features/vehicles/presentation/vehicle_form_screen.dart';

import '../support/preview_support.dart';

void main() {
  setUpAll(loadFonts);

  final shots = <String, Widget>{
    'splash': const SplashScreen(),
    'login': const AuthChoiceScreen(),
    'login_signup': const SignupScreen(),
    'login_password': const LoginScreen(),
    'login_password_forgot': const ForgotPasswordScreen(),
    'otp': const OtpScreen(
        name: 'حسين', phone: '+9647701234567', password: 'correct-horse'),
    'detail': const VehicleDetailScreen(vehicleId: '1'),
    'detail_unlinked': const VehicleDetailScreen(vehicleId: '2'),
    'form': const VehicleFormScreen(),
    'thread': const ContactThreadScreen(requestId: 'a'),
    'notifications': const NotificationsScreen(),
    'subscription': const SubscriptionScreen(),
    'visibility': const VisibilityScreen(),
    'server': const ServerSetupScreen(),
  };

  for (final e in shots.entries) {
    testWidgets(e.key, (tester) async {
      await shoot(tester, screen: e.value, name: e.key);
    });
  }
}
