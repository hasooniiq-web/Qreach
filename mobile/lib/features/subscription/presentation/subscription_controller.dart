import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/locale/locale_controller.dart';
import '../../../core/providers.dart';
import '../data/subscription_models.dart';

/// Current plan + price + status + all plans. Invalidate after an upgrade
/// request so `pendingUpgrade` refreshes.
final subscriptionProvider =
    FutureProvider.autoDispose<SubscriptionInfo>((ref) {
  // Plan names come back in the app language, so a language change re-fetches.
  ref.watch(localeProvider);
  return ref.watch(subscriptionRepositoryProvider).fetch();
});
