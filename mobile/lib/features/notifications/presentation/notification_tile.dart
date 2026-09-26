import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../data/notification_model.dart';

IconData notificationIcon(NotificationModel n) {
  if (n.type == 'contact_request') return Icons.forum_outlined;
  return switch (n.actionType) {
    'emergency' => Icons.warning_amber_rounded,
    'vehicle_block' => Icons.directions_car_outlined,
    'lights_on' => Icons.lightbulb_outline,
    'important_message' => Icons.chat_bubble_outline,
    _ => Icons.notifications_none,
  };
}

/// One notification row: type icon, title, vehicle/message, time, and an
/// unread marker. Emergencies get the brand-yellow icon disc so they stand
/// out without turning the list red; unread state is a dot *and* a bolder
/// title, never color alone.
class NotificationTile extends StatelessWidget {
  const NotificationTile({super.key, required this.notification, this.onTap});

  final NotificationModel notification;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final n = notification;
    final subtitle = [
      if (n.vehicle != null) n.vehicle!,
      if (n.message != null && n.message!.isNotEmpty) n.message!,
    ].join(' · ');

    return Semantics(
      label:
          '${n.isEmergency ? '${l10n.emergencyLabel}: ' : ''}${n.title}${n.read ? '' : ', ${l10n.unreadLabel}'}',
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(
              horizontal: AppSpacing.lg, vertical: AppSpacing.md),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color:
                      n.isEmergency ? AppColors.brandYellow : AppColors.ink100,
                  shape: BoxShape.circle,
                ),
                child: Icon(notificationIcon(n),
                    color:
                        n.isEmergency ? AppColors.onSurface : AppColors.ink700,
                    size: 22),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(n.title,
                        style: TextStyle(
                            fontWeight:
                                n.read ? FontWeight.w500 : FontWeight.w800)),
                    if (subtitle.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 2),
                        child: Text(subtitle,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                                color: AppColors.ink500, fontSize: 13)),
                      ),
                  ],
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(notificationTime(n.createdAt),
                      style: TextStyle(color: AppColors.ink400, fontSize: 12)),
                  const SizedBox(height: 6),
                  if (!n.read)
                    Container(
                      width: 10,
                      height: 10,
                      decoration: BoxDecoration(
                          color: AppColors.onSurface, shape: BoxShape.circle),
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// "14:05" for today/yesterday-style rows, full date otherwise.
String notificationTime(DateTime time) {
  final local = time.toLocal();
  final now = DateTime.now();
  final sameDay = local.year == now.year &&
      local.month == now.month &&
      local.day == now.day;
  return sameDay
      ? DateFormat('HH:mm', 'en').format(local)
      : DateFormat('MM-dd HH:mm', 'en').format(local);
}
