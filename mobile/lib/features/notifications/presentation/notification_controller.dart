import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../data/notification_model.dart';

/// The notification feed plus unread count. Read-state changes are applied
/// optimistically (the list updates instantly) and rolled back to the
/// server's truth by a refresh if the request fails.
class NotificationController extends AsyncNotifier<NotificationFeed> {
  @override
  Future<NotificationFeed> build() =>
      ref.read(notificationRepositoryProvider).fetch();

  Future<void> refresh() async {
    state = await AsyncValue.guard(
        () => ref.read(notificationRepositoryProvider).fetch());
  }

  Future<void> markRead(NotificationModel notification) async {
    final feed = state.valueOrNull;
    if (feed == null || notification.read) return;

    state = AsyncData(NotificationFeed(
      items: [
        for (final n in feed.items)
          n.id == notification.id ? n.copyWith(read: true) : n,
      ],
      unreadCount: (feed.unreadCount - 1).clamp(0, 1 << 30),
    ));

    try {
      await ref.read(notificationRepositoryProvider).markRead(notification.id);
    } catch (_) {
      await refresh();
    }
  }

  Future<void> markAllRead() async {
    final feed = state.valueOrNull;
    if (feed == null || feed.unreadCount == 0) return;

    state = AsyncData(NotificationFeed(
      items: [for (final n in feed.items) n.copyWith(read: true)],
      unreadCount: 0,
    ));

    try {
      await ref.read(notificationRepositoryProvider).markAllRead();
    } catch (_) {
      await refresh();
    }
  }
}

final notificationsProvider =
    AsyncNotifierProvider<NotificationController, NotificationFeed>(
  NotificationController.new,
);
