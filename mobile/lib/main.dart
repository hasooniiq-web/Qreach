import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Only Android has a registered Firebase config (android/app/google-
  // services.json) so far — see docs/MOBILE_SECURITY.md. Guard by platform
  // so an iOS/web build doesn't crash at startup with no native config file
  // for Firebase.initializeApp() to read.
  if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
    // Push is optional: if Firebase can't start (missing Play Services, bad
    // config) the app must still open and work, just without notifications.
    try {
      await Firebase.initializeApp();
    } catch (e) {
      debugPrint('Firebase init failed, continuing without push: $e');
    }
  }

  runApp(const ProviderScope(child: QreachApp()));
}
