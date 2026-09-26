import 'package:flutter/material.dart';

import '../../core/design/motion.dart';
import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';

/// The one surface recipe: a rounded, softly lifted panel (a hairline border in
/// dark mode, where shadows disappear). Tappable cards give press feedback.
/// Use it only where grouping genuinely helps — not around every row.
class AppCard extends StatelessWidget {
  const AppCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.onTap,
    this.color,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final VoidCallback? onTap;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final card = Container(
      decoration: BoxDecoration(
        color: color ?? AppColors.surface,
        borderRadius: AppRadius.rMd,
        boxShadow: AppShadows.card,
        border: AppShadows.hairline,
      ),
      padding: padding,
      // Lets ListTiles/switches inside paint their ink over the card surface.
      child: Material(type: MaterialType.transparency, child: child),
    );

    if (onTap == null) return card;
    return Pressable(onTap: onTap, scale: 0.985, child: card);
  }
}
