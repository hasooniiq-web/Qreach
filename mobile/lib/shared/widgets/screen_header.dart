import 'package:flutter/material.dart';

import '../../core/design/motion.dart';
import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';

/// The large title used at the top of every tab screen: a big, confident
/// heading with an optional supporting line and a trailing control. It replaces
/// the small centred app bar so tab screens read as pages, not forms.
class ScreenHeader extends StatelessWidget {
  const ScreenHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.trailing,
  });

  final String title;
  final String? subtitle;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 20, 16, 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: AppText.display(context)),
                if (subtitle != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text(subtitle!,
                        style: AppText.body(context)
                            .copyWith(color: AppColors.ink500)),
                  ),
              ],
            ),
          ),
          if (trailing != null) trailing!,
        ],
      ),
    );
  }
}

/// A round icon button used beside a [ScreenHeader] title.
class HeaderIconButton extends StatelessWidget {
  const HeaderIconButton({
    super.key,
    required this.icon,
    required this.onTap,
    required this.tooltip,
    this.filled = false,
  });

  final IconData icon;
  final VoidCallback onTap;
  final String tooltip;

  /// Yellow, for the screen's main action.
  final bool filled;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      label: tooltip,
      child: Pressable(
        onTap: onTap,
        haptic: true,
        child: Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: filled ? AppColors.brandYellow : AppColors.surface,
            boxShadow: filled ? null : AppShadows.card,
            border: filled ? null : AppShadows.hairline,
          ),
          child: Icon(icon,
              size: 24,
              color: filled ? AppColors.brandBlack : AppColors.ink900),
        ),
      ),
    );
  }
}
