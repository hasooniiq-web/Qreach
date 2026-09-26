import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/skeleton.dart';
import '../../../shared/widgets/status_badge.dart';
import '../../notifications/presentation/notification_tile.dart';
import '../data/contact_request_model.dart';
import 'contact_controller.dart';

String contactStatusLabel(AppLocalizations l10n, String status) =>
    switch (status) {
      'new' => l10n.contactStatusNew,
      'read' => l10n.contactStatusRead,
      'responded' => l10n.contactStatusResponded,
      _ => l10n.contactStatusClosed,
    };

/// The owner's inbox of visitor messages — everything people sent after
/// scanning one of their vehicle codes. Opening a message marks it read;
/// the owner can mark it as replied, or call the visitor back when the
/// visitor chose to leave a number.
class ContactsScreen extends ConsumerWidget {
  const ContactsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final async = ref.watch(contactsProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.contactsTitle)),
      body: async.when(
        loading: () => const SkeletonList(count: 4),
        error: (error, _) => ErrorState(
            error: error,
            onRetry: () => ref.read(contactsProvider.notifier).refresh()),
        data: (feed) => RefreshIndicator(
          onRefresh: () => ref.read(contactsProvider.notifier).refresh(),
          child: feed.items.isEmpty
              ? ListView(children: [
                  SizedBox(
                    height: MediaQuery.of(context).size.height * 0.65,
                    child: EmptyState(
                      icon: Icons.forum_outlined,
                      title: l10n.contactsEmptyTitle,
                      subtitle: l10n.contactsEmptySubtitle,
                    ),
                  ),
                ])
              : ListView.separated(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  itemCount: feed.items.length,
                  separatorBuilder: (_, __) =>
                      const SizedBox(height: AppSpacing.md),
                  itemBuilder: (context, i) =>
                      _ContactCard(request: feed.items[i]),
                ),
        ),
      ),
    );
  }
}

class _ContactCard extends ConsumerWidget {
  const _ContactCard({required this.request});

  final ContactRequestModel request;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final r = request;

    return AppCard(
      onTap: () {
        ref.read(contactsProvider.notifier).markRead(r);
        context.push('/contacts/${r.id}');
      },
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 22,
            backgroundColor: r.isNew ? AppColors.brandYellow : AppColors.ink100,
            child: Text(
              (r.visitorName?.isNotEmpty == true
                      ? r.visitorName!
                      : l10n.visitorAnonymous)
                  .characters
                  .first,
              style: const TextStyle(
                  color: AppColors.brandBlack, fontWeight: FontWeight.w800),
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        r.visitorName?.isNotEmpty == true
                            ? r.visitorName!
                            : l10n.visitorAnonymous,
                        style: TextStyle(
                            fontWeight:
                                r.isNew ? FontWeight.w800 : FontWeight.w600),
                      ),
                    ),
                    Text(notificationTime(r.createdAt),
                        style: const TextStyle(
                            color: AppColors.ink400, fontSize: 12)),
                  ],
                ),
                if (r.vehicle != null)
                  Text(l10n.contactAboutVehicle(r.vehicle!),
                      style: const TextStyle(
                          color: AppColors.ink500, fontSize: 12)),
                const SizedBox(height: 4),
                Text(r.message,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        color: r.isNew ? AppColors.ink900 : AppColors.ink600)),
                const SizedBox(height: AppSpacing.sm),
                StatusBadge(
                  label: contactStatusLabel(l10n, r.status),
                  tone: r.isNew
                      ? StatusTone.warning
                      : r.isHandled
                          ? StatusTone.success
                          : StatusTone.neutral,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
