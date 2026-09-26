import 'package:flutter/material.dart';

import '../../core/design/motion.dart';
import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_spacing.dart';

/// A titled group of rows on one rounded surface with hairline dividers — the
/// "grouped settings, not dozens of separate cards" pattern.
class SettingsGroup extends StatelessWidget {
  const SettingsGroup(
      {super.key, required this.title, required this.rows, this.index = 2});

  final String title;

  /// Position in the screen's staggered entrance.
  final int index;
  final List<Widget> rows;

  @override
  Widget build(BuildContext context) {
    return FadeSlideIn(
        index: index,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsetsDirectional.only(
                  start: AppSpacing.xs, bottom: AppSpacing.sm),
              child: Text(title,
                  style: TextStyle(
                      color: AppColors.ink500,
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.2)),
            ),
            Container(
              clipBehavior: Clip.antiAlias,
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: AppRadius.rMd,
                boxShadow: AppShadows.card,
                border: AppShadows.hairline,
              ),
              child: Material(
                type: MaterialType.transparency,
                child: Column(
                  children: [
                    for (var i = 0; i < rows.length; i++) ...[
                      if (i > 0)
                        Divider(
                            height: 1,
                            indent: 68,
                            endIndent: 0,
                            color: AppColors.ink100),
                      rows[i],
                    ],
                  ],
                ),
              ),
            ),
          ],
        ));
  }
}

/// One row: a tinted icon tile, a label with optional secondary text, and a
/// chevron when tappable. The whole row gives press feedback.
class SettingsRow extends StatelessWidget {
  const SettingsRow({
    super.key,
    required this.icon,
    required this.label,
    this.value,
    this.trailing,
    this.onTap,
    this.destructive = false,
  });

  final IconData icon;
  final String label;
  final String? value;
  final Widget? trailing;
  final VoidCallback? onTap;
  final bool destructive;

  @override
  Widget build(BuildContext context) {
    final color = destructive ? AppColors.statusDanger : AppColors.ink900;
    final tile = destructive
        ? AppColors.statusDanger.withValues(alpha: 0.12)
        : AppColors.ink100;

    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.lg, vertical: AppSpacing.md),
        child: Row(
          children: [
            Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                color: tile,
                borderRadius: BorderRadius.circular(11),
              ),
              child: Icon(icon,
                  size: 20, color: destructive ? color : AppColors.ink700),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label,
                      style: TextStyle(
                          color: color,
                          fontSize: 15,
                          fontWeight: FontWeight.w600)),
                  if (value != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child: Text(value!,
                          style: TextStyle(
                              color: AppColors.ink500, fontSize: 12.5)),
                    ),
                ],
              ),
            ),
            trailing ??
                (onTap != null && !destructive
                    ? Icon(Icons.chevron_left_rounded, color: AppColors.ink300)
                    : const SizedBox.shrink()),
          ],
        ),
      ),
    );
  }
}
