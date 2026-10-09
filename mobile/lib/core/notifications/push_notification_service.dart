import 'dart:async';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import '../network/api_client.dart';

/// Where a tapped notification should take the user, derived from the FCM
/// `data` payload the backend sends (see App\Notifications\*::toFcm()).
String? routeForPush(Map<String, dynamic> data) {
  switch (data['type']) {
    case 'contact_request':
      final id = data['contact_request_id'];
      return id is String && id.isNotEmpty ? '/contacts/$id' : '/contacts';
    case 'tag_action':
      return '/notifications';
  }
  return null;
}

/// Registers this device for Firebase Cloud Messaging and keeps the
/// backend's `devices.push_token` in sync. Android and iOS both have a
/// registered Firebase config now (see docs/MOBILE_SECURITY.md) — iOS still
/// goes through FCM, not raw APNs, since firebase_messaging relays through
/// it, so `push_provider` is "fcm" on both platforms and only the
/// `platform` value differs.
class PushNotificationService {
  PushNotificationService(this._client);

  final ApiClient _client;

  StreamSubscription<String>? _refreshSub;
  StreamSubscription<RemoteMessage>? _openedSub;

  /// Last token successfully sent, so a refresh/retry doesn't re-send it.
  String? _sentToken;

  bool get _supported =>
      !kIsWeb &&
      (defaultTargetPlatform == TargetPlatform.android ||
          defaultTargetPlatform == TargetPlatform.iOS);

  /// Wires notification taps to navigation. [open] receives a route such as
  /// `/contacts/<id>`. Handles both "app in background" and "app was closed".
  Future<void> listenForTaps(void Function(String route) open) async {
    if (!_supported) return;
    try {
      final messaging = FirebaseMessaging.instance;

      // iOS shows nothing while the app is open unless asked to; Android
      // already shows a heads-up through the notification channel.
      await messaging.setForegroundNotificationPresentationOptions(
          alert: true, badge: true, sound: true);

      void handle(RemoteMessage m) {
        final route = routeForPush(m.data);
        if (route != null) open(route);
      }

      await _openedSub?.cancel();
      _openedSub = FirebaseMessaging.onMessageOpenedApp.listen(handle);

      final initial = await messaging.getInitialMessage();
      if (initial != null) handle(initial);
    } catch (e) {
      debugPrint('Push tap listener not started: $e');
    }
  }

  Future<void> registerForPushNotifications() async {
    if (!_supported) return;

    // A new login (possibly another account) must register the token again.
    _sentToken = null;

    try {
      final messaging = FirebaseMessaging.instance;
      final settings = await messaging.requestPermission();
      if (settings.authorizationStatus == AuthorizationStatus.denied) return;

      // FCM tokens rotate (app reinstall, data clear, or periodic refresh).
      // Subscribed *before* anything that can return early: on iOS the first
      // FCM token is often only produced once the APNs handshake finishes,
      // which can be after the wait below gives up — this listener is what
      // still delivers it then.
      await _refreshSub?.cancel();
      _refreshSub = messaging.onTokenRefresh.listen(_sendTokenToBackend);

      // iOS needs the APNs token registered with Apple before FCM can hand
      // out a token — getToken() throws if it isn't ready yet, and right
      // after requestPermission() on a fresh install it usually isn't.
      if (defaultTargetPlatform == TargetPlatform.iOS) {
        var apnsToken = await messaging.getAPNSToken();
        var attempts = 0;
        while (apnsToken == null && attempts < 30) {
          await Future.delayed(const Duration(seconds: 1));
          apnsToken = await messaging.getAPNSToken();
          attempts++;
        }
        if (apnsToken == null) {
          // No APNs token after 30s: almost always a build/signing problem
          // (Push Notifications capability missing from the App ID or
          // provisioning profile, or running on the simulator). Nothing the
          // app can do at runtime; onTokenRefresh above still fires if it
          // arrives later.
          debugPrint(
              'APNs token unavailable — check the Push Notifications capability and provisioning profile.');
          return;
        }
      }

      final token = await messaging.getToken();
      if (token != null) {
        await _sendTokenToBackend(token);
      }
    } catch (e) {
      // Push is optional; never let it break login or app start.
      debugPrint('Push registration failed: $e');
    }
  }

  Future<void> _sendTokenToBackend(String token) async {
    if (token == _sentToken) return;
    try {
      await _client.dio.put('/devices/push-token', data: {
        'platform':
            defaultTargetPlatform == TargetPlatform.iOS ? 'ios' : 'android',
        'push_provider': 'fcm',
        'push_token': token,
      });
      _sentToken = token;
    } catch (_) {
      // Best-effort: a failed registration (e.g. not logged in yet, or a
      // transient network error) shouldn't crash app startup or login.
      // onTokenRefresh will fire again, and the next login retries too.
    }
  }
}
