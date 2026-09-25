import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import '../network/api_client.dart';

/// Registers this device for Firebase Cloud Messaging and keeps the
/// backend's `devices.push_token` in sync. Android-only for now — no iOS
/// Firebase config (GoogleService-Info.plist) has been registered yet, see
/// docs/MOBILE_SECURITY.md.
class PushNotificationService {
  PushNotificationService(this._client);

  final ApiClient _client;

  Future<void> registerForPushNotifications() async {
    if (kIsWeb || defaultTargetPlatform != TargetPlatform.android) return;

    try {
      final messaging = FirebaseMessaging.instance;
      final settings = await messaging.requestPermission();
      if (settings.authorizationStatus == AuthorizationStatus.denied) return;

      final token = await messaging.getToken();
      if (token != null) {
        await _sendTokenToBackend(token);
      }

      // FCM tokens rotate (app reinstall, data clear, or periodic refresh) —
      // without this, a device would silently stop receiving pushes after a
      // rotation until the next login.
      messaging.onTokenRefresh.listen(_sendTokenToBackend);
    } catch (_) {
      // Push is optional; never let it break login or app start.
    }
  }

  Future<void> _sendTokenToBackend(String token) async {
    try {
      await _client.dio.put('/devices/push-token', data: {
        'platform': 'android',
        'push_provider': 'fcm',
        'push_token': token,
      });
    } catch (_) {
      // Best-effort: a failed registration (e.g. not logged in yet, or a
      // transient network error) shouldn't crash app startup or login.
      // onTokenRefresh will fire again, and the next login retries too.
    }
  }
}
