import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/design/motion.dart';
import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';
import '../../features/notifications/presentation/notification_controller.dart';
import '../../l10n/app_localizations.dart';

/// The bell on the home screen — the only place notifications surface. A soft
/// round button; the unread badge pops in when the count rises from zero and
/// the count itself changes smoothly.
class NotificationBell extends ConsumerWidget {
  const NotificationBell({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final unread =
        ref.watch(notificationsProvider).valueOrNull?.unreadCount ?? 0;

    return Semantics(
      button: true,
      label: l10n.notifications,
      child: Pressable(
        onTap: () => context.push('/notifications'),
        haptic: true,
        child: SizedBox(
          width: 48,
          height: 48,
          child: Stack(
            alignment: Alignment.center,
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  shape: BoxShape.circle,
                  boxShadow: AppShadows.card,
                  border: AppShadows.hairline,
                ),
                child: Icon(
                    unread > 0
                        ? Icons.notifications_active_rounded
                        : Icons.notifications_none_rounded,
                    size: 23,
                    color: AppColors.ink900),
              ),
              PositionedDirectional(
                top: 2,
                end: 2,
                child: AnimatedScale(
                  scale: unread > 0 ? 1 : 0,
                  duration: AppMotion.base,
                  curve: AppMotion.settle,
                  child: Container(
                    constraints:
                        const BoxConstraints(minWidth: 20, minHeight: 20),
                    padding: const EdgeInsets.symmetric(horizontal: 5),
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: AppColors.brandYellow,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: AppColors.background, width: 2),
                    ),
                    child: Text(unread > 9 ? '9+' : '$unread',
                        style: const TextStyle(
                            color: AppColors.brandBlack,
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            height: 1.2)),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
