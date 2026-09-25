import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../data/subscription_models.dart';

/// Current plan + price + status + all plans. Invalidate after an upgrade
/// request so `pendingUpgrade` refreshes.
final subscriptionProvider =
    FutureProvider.autoDispose<SubscriptionInfo>((ref) {
  return ref.watch(subscriptionRepositoryProvider).fetch();
});

final publicContactProvider =
    FutureProvider.autoDispose<PublicContactModel>((ref) {
  return ref.watch(subscriptionRepositoryProvider).fetchPublicContact();
});
