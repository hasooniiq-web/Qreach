import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';

/// Whether the first-launch walkthrough has been shown on this device.
/// `null` while the stored flag is being read (the router keeps the splash up),
/// then `true`/`false`. Kept in the same secure storage as the other app flags.
class OnboardingController extends StateNotifier<bool?> {
  OnboardingController(this._ref) : super(null) {
    _load();
  }

  static const _key = 'onboarding_seen_v1';

  final Ref _ref;

  Future<void> _load() async {
    try {
      final value = await _ref.read(secureStorageProvider).readValue(_key);
      state = value == '1';
    } catch (_) {
      // Unreadable storage must never trap the user on a screen: skip it.
      state = true;
    }
  }

  /// Called by both "Skip" and the last page's button.
  Future<void> complete() async {
    state = true;
    try {
      await _ref.read(secureStorageProvider).writeValue(_key, '1');
    } catch (_) {}
  }
}

final onboardingSeenProvider =
    StateNotifierProvider<OnboardingController, bool?>(
        (ref) => OnboardingController(ref));
