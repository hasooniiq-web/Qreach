import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../features/notifications/presentation/notification_controller.dart';
import '../../l10n/app_localizations.dart';

/// The bell in every tab's top bar: opens the notification center and shows
/// how many alerts are unread.
class NotificationBell extends ConsumerWidget {
  const NotificationBell({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final unread =
        ref.watch(notificationsProvider).valueOrNull?.unreadCount ?? 0;

    return IconButton(
      tooltip: l10n.notifications,
      onPressed: () => context.push('/notifications'),
      icon: Badge(
        isLabelVisible: unread > 0,
        label: Text(unread > 9 ? '9+' : '$unread'),
        backgroundColor: AppColors.brandYellow,
        textColor: AppColors.brandBlack,
        child: const Icon(Icons.notifications_none_rounded, size: 26),
      ),
    );
  }
}
