import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import '../network/api_client.dart';

/// Registers this device for Firebase Cloud Messaging and keeps the
/// backend's `devices.push_token` in sync. Android and iOS both have a
/// registered Firebase config now (see docs/MOBILE_SECURITY.md) — iOS still
/// goes through FCM, not raw APNs, since firebase_messaging relays through
/// it, so `push_provider` is "fcm" on both platforms and only the
/// `platform` value differs.
class PushNotificationService {
  PushNotificationService(this._client);

  final ApiClient _client;

  Future<void> registerForPushNotifications() async {
    if (kIsWeb ||
        (defaultTargetPlatform != TargetPlatform.android &&
            defaultTargetPlatform != TargetPlatform.iOS)) {
      return;
    }

    try {
      final messaging = FirebaseMessaging.instance;
      final settings = await messaging.requestPermission();
      if (settings.authorizationStatus == AuthorizationStatus.denied) return;

      // iOS needs the APNs token registered with Apple before FCM can hand
      // out a token — getToken() throws if it isn't ready yet, and right
      // after requestPermission() on a fresh install it usually isn't: the
      // handshake with Apple's servers hasn't completed. Without this wait,
      // this whole call fails here, every single time, on first login —
      // this call only ever runs once per login, so there is no later retry.
      if (defaultTargetPlatform == TargetPlatform.iOS) {
        var apnsToken = await messaging.getAPNSToken();
        var attempts = 0;
        while (apnsToken == null && attempts < 10) {
          await Future.delayed(const Duration(seconds: 1));
          apnsToken = await messaging.getAPNSToken();
          attempts++;
        }
        // Still not ready after 10s: give up for now rather than crash:
        // onTokenRefresh below will pick it up if/when it does arrive.
        if (apnsToken == null) return;
      }

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
        'platform':
            defaultTargetPlatform == TargetPlatform.iOS ? 'ios' : 'android',
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
