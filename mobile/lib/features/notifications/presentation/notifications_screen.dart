import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/design/motion.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/skeleton.dart';
import '../data/notification_model.dart';
import 'notification_controller.dart';
import 'notification_tile.dart';

/// The notification center: grouped by day (today / yesterday / earlier),
/// unread items marked, tap to mark one read, or mark everything read.
class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final async = ref.watch(notificationsProvider);
    final unread = async.valueOrNull?.unreadCount ?? 0;

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.notifications),
        actions: [
          if (unread > 0)
            TextButton(
              onPressed: () =>
                  ref.read(notificationsProvider.notifier).markAllRead(),
              child: Text(l10n.markAllRead),
            ),
        ],
      ),
      body: SwapFade(
        child: async.when(
          loading: () => ListView(
            key: const ValueKey('loading'),
            physics: const NeverScrollableScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
            children: const [
              SkeletonRow(),
              SkeletonRow(),
              SkeletonRow(),
              SkeletonRow(),
              SkeletonRow(),
            ],
          ),
          error: (error, _) => ErrorState(
              key: const ValueKey('error'),
              error: error,
              onRetry: () =>
                  ref.read(notificationsProvider.notifier).refresh()),
          data: (feed) => RefreshIndicator(
            onRefresh: () => ref.read(notificationsProvider.notifier).refresh(),
            child: feed.items.isEmpty
                ? ListView(children: [
                    SizedBox(
                      height: MediaQuery.of(context).size.height * 0.7,
                      child: EmptyState(
                        icon: Icons.notifications_rounded,
                        title: l10n.notificationsEmptyTitle,
                        subtitle: l10n.notificationsEmptySubtitle,
                      ),
                    ),
                  ])
                : _GroupedList(items: feed.items),
          ),
        ),
      ),
    );
  }
}

class _GroupedList extends ConsumerWidget {
  const _GroupedList({required this.items});

  final List<NotificationModel> items;

  String _group(BuildContext context, DateTime time) {
    final l10n = AppLocalizations.of(context)!;
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final day =
        DateTime(time.toLocal().year, time.toLocal().month, time.toLocal().day);
    final diff = today.difference(day).inDays;
    if (diff <= 0) return l10n.groupToday;
    if (diff == 1) return l10n.groupYesterday;
    return l10n.groupEarlier;
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    // Items arrive newest-first, so consecutive rows share a group.
    final rows = <Widget>[];
    String? current;

    for (final n in items) {
      final group = _group(context, n.createdAt);
      if (group != current) {
        current = group;
        rows.add(Padding(
          padding: const EdgeInsets.fromLTRB(
              AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.xs),
          child: Text(group,
              style: TextStyle(
                  color: AppColors.ink500,
                  fontWeight: FontWeight.w700,
                  fontSize: 13)),
        ));
      }
      final tile = NotificationTile(
        notification: n,
        onTap: () => ref.read(notificationsProvider.notifier).markRead(n),
      );
      rows.add(FadeSlideIn(
        index: rows.length ~/ 2,
        offset: 12,
        // Swipe an unread alert to mark it read; the row springs back.
        child: n.read
            ? tile
            : Dismissible(
                key: ValueKey('n-${n.id}'),
                direction: DismissDirection.startToEnd,
                confirmDismiss: (_) async {
                  HapticFeedback.selectionClick();
                  ref.read(notificationsProvider.notifier).markRead(n);
                  return false;
                },
                background: Container(
                  color: AppColors.brandYellow,
                  alignment: AlignmentDirectional.centerStart,
                  padding:
                      const EdgeInsets.symmetric(horizontal: AppSpacing.xl),
                  child: const Icon(Icons.done_all_rounded,
                      color: AppColors.brandBlack),
                ),
                child: tile,
              ),
      ));
      rows.add(const Divider(
          height: 1, indent: AppSpacing.lg, endIndent: AppSpacing.lg));
    }

    return ListView(children: rows);
  }
}
