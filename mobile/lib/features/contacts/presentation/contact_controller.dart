import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../data/contact_request_model.dart';

/// The owner's inbox. Status changes apply instantly and fall back to the
/// server's truth (a refresh) if the request fails.
class ContactController extends AsyncNotifier<ContactRequestFeed> {
  @override
  Future<ContactRequestFeed> build() =>
      ref.read(contactRequestRepositoryProvider).fetch();

  Future<void> refresh() async {
    state = await AsyncValue.guard(
        () => ref.read(contactRequestRepositoryProvider).fetch());
  }

  void _apply(String id, String status) {
    final feed = state.valueOrNull;
    if (feed == null) return;

    final items = [
      for (final r in feed.items) r.id == id ? r.copyWith(status: status) : r,
    ];
    state = AsyncData(ContactRequestFeed(
        items: items, unreadCount: items.where((r) => r.isNew).length));
  }

  Future<void> markRead(ContactRequestModel request) async {
    if (!request.isNew) return;
    _apply(request.id, 'read');
    try {
      await ref.read(contactRequestRepositoryProvider).markRead(request.id);
    } catch (_) {
      await refresh();
    }
  }

  Future<void> markResponded(ContactRequestModel request) async {
    if (request.isHandled) return;
    _apply(request.id, 'responded');
    try {
      await ref
          .read(contactRequestRepositoryProvider)
          .markResponded(request.id);
    } catch (_) {
      await refresh();
    }
  }
}

final contactsProvider =
    AsyncNotifierProvider<ContactController, ContactRequestFeed>(
  ContactController.new,
);
