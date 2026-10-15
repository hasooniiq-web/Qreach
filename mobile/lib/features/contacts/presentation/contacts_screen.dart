import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/design/motion.dart';
import '../../../core/design/tokens.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/screen_header.dart';
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
/// scanning one of their vehicle codes. Opening one marks it read and opens
/// the conversation, where the owner replies.
class ContactsScreen extends ConsumerWidget {
  const ContactsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final async = ref.watch(contactsProvider);
    final unread = async.valueOrNull?.unreadCount ?? 0;

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            ScreenHeader(
              title: l10n.contactsTitle,
              trailing: AnimatedSwitcher(
                duration: AppMotion.base,
                child: unread > 0
                    ? Container(
                        key: const ValueKey('unread'),
                        margin: const EdgeInsets.only(bottom: 6),
                        padding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: AppColors.brandYellow,
                          borderRadius: BorderRadius.circular(AppRadius.pill),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            AnimatedCount(
                              value: unread,
                              style: const TextStyle(
                                  color: AppColors.brandBlack,
                                  fontWeight: FontWeight.w800),
                            ),
                            const SizedBox(width: 6),
                            Text(l10n.contactStatusNew,
                                style: const TextStyle(
                                    color: AppColors.brandBlack,
                                    fontWeight: FontWeight.w700,
                                    fontSize: 13)),
                          ],
                        ),
                      )
                    : const SizedBox.shrink(key: ValueKey('none')),
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                color: AppColors.onSurface,
                backgroundColor: AppColors.surface,
                onRefresh: () => ref.read(contactsProvider.notifier).refresh(),
                child: SwapFade(
                  child: async.when(
                    loading: () => ListView(
                      key: const ValueKey('loading'),
                      physics: const NeverScrollableScrollPhysics(),
                      padding:
                          const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
                      children: const [
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
                            ref.read(contactsProvider.notifier).refresh()),
                    data: (feed) => feed.items.isEmpty
                        ? ListView(
                            key: const ValueKey('empty'),
                            children: [
                              SizedBox(
                                height:
                                    MediaQuery.of(context).size.height * 0.6,
                                child: EmptyState(
                                  icon: Icons.forum_rounded,
                                  title: l10n.contactsEmptyTitle,
                                  subtitle: l10n.contactsEmptySubtitle,
                                ),
                              ),
                            ],
                          )
                        : ListView.separated(
                            key: const ValueKey('data'),
                            separatorBuilder: (context, i) => Divider(
                                height: 1,
                                thickness: 1,
                                indent: AppSpacing.md,
                                endIndent: AppSpacing.md,
                                color: AppColors.ink200),
                            padding: const EdgeInsets.fromLTRB(
                                AppSpacing.sm, 0, AppSpacing.sm, 120),
                            itemCount: feed.items.length,
                            itemBuilder: (context, i) => FadeSlideIn(
                              index: i,
                              child: _ContactRow(request: feed.items[i]),
                            ),
                          ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ContactRow extends ConsumerWidget {
  const _ContactRow({required this.request});

  final ContactRequestModel request;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final r = request;
    final named = r.visitorName?.isNotEmpty == true;

    return Pressable(
      scale: 0.985,
      onTap: () {
        if (!r.isReporter) ref.read(contactsProvider.notifier).markRead(r);
        context.push('/contacts/${r.id}');
      },
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 2),
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.md, vertical: AppSpacing.md),
        decoration: BoxDecoration(
          borderRadius: AppRadius.rMd,
          // An unread request gets a faint yellow wash, not a heavier card.
          color: r.isNew
              ? AppColors.brandYellow
                  .withValues(alpha: AppColors.dark ? 0.10 : 0.14)
              : Colors.transparent,
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: r.isNew ? AppColors.brandYellow : AppColors.ink100,
              ),
              alignment: Alignment.center,
              child: named
                  ? Text(r.visitorName!.characters.first,
                      style: TextStyle(
                          color:
                              r.isNew ? AppColors.brandBlack : AppColors.ink700,
                          fontSize: 19,
                          fontWeight: FontWeight.w800))
                  : Icon(
                      r.isReporter
                          ? Icons.directions_car_outlined
                          : Icons.person_outline_rounded,
                      color: r.isNew ? AppColors.brandBlack : AppColors.ink500),
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
                          r.isReporter
                              ? (r.vehicle ?? l10n.contactWithOwner)
                              : (named ? r.visitorName! : l10n.visitorAnonymous),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                              fontSize: 15.5,
                              color: AppColors.ink900,
                              fontWeight:
                                  r.isNew ? FontWeight.w800 : FontWeight.w600),
                        ),
                      ),
                      Text(notificationTime(r.createdAt),
                          style: TextStyle(
                              color: AppColors.ink400,
                              fontSize: 11.5,
                              fontWeight: FontWeight.w600)),
                    ],
                  ),
                  if (r.vehicle != null && !r.isReporter)
                    Padding(
                      padding: const EdgeInsets.only(top: 1),
                      child: Row(
                        children: [
                          Icon(Icons.directions_car_outlined,
                              size: 14, color: AppColors.ink400),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(r.vehicle!,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                    color: AppColors.ink500, fontSize: 12.5)),
                          ),
                        ],
                      ),
                    ),
                  const SizedBox(height: 5),
                  Text(r.message,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                          color: r.isNew ? AppColors.ink900 : AppColors.ink600,
                          fontSize: 14,
                          height: 1.45)),
                  const SizedBox(height: AppSpacing.sm),
                  StatusBadge(
                    label: r.isReporter
                        ? l10n.contactStartedByYou
                        : contactStatusLabel(l10n, r.status),
                    tone: r.isReporter
                        ? StatusTone.neutral
                        : r.isNew
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
      ),
    );
  }
}
