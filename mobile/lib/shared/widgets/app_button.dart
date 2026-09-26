import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/design/motion.dart';
import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';

enum AppButtonStyle { primary, accent, secondary, ghost, danger }

/// The app's button. Presses in with a small scale and a light haptic, morphs
/// into a spinner while [loading], and keeps a 52dp touch height. Prefer it
/// over raw Material buttons for every primary action.
class AppButton extends StatelessWidget {
  const AppButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.icon,
    this.loading = false,
    this.expand = true,
    this.style = AppButtonStyle.primary,
  });

  final String label;
  final VoidCallback? onPressed;
  final IconData? icon;
  final bool loading;
  final bool expand;
  final AppButtonStyle style;

  @override
  Widget build(BuildContext context) {
    final dark = AppColors.dark;
    final (bg, fg, border) = switch (style) {
      AppButtonStyle.primary => (
          dark ? AppColors.brandYellow : AppColors.brandBlack,
          dark ? AppColors.brandBlack : AppColors.brandWhite,
          null as Color?
        ),
      AppButtonStyle.accent => (
          AppColors.brandYellow,
          AppColors.brandBlack,
          null as Color?
        ),
      AppButtonStyle.secondary => (
          Colors.transparent,
          AppColors.ink900,
          AppColors.ink200 as Color?
        ),
      AppButtonStyle.ghost => (
          Colors.transparent,
          AppColors.ink900,
          null as Color?
        ),
      AppButtonStyle.danger => (
          Colors.transparent,
          AppColors.statusDanger,
          AppColors.statusDanger as Color?
        ),
    };
    final enabled = onPressed != null && !loading;

    return Pressable(
      onTap: enabled
          ? () {
              HapticFeedback.lightImpact();
              onPressed!();
            }
          : null,
      scale: 0.96,
      child: AnimatedOpacity(
        duration: AppMotion.fast,
        opacity: onPressed == null && !loading ? 0.45 : 1,
        child: AnimatedContainer(
          duration: AppMotion.base,
          curve: AppMotion.standard,
          height: 52,
          constraints: BoxConstraints(minWidth: expand ? 0 : 140),
          padding: const EdgeInsets.symmetric(horizontal: 22),
          decoration: BoxDecoration(
            color: bg,
            borderRadius: AppRadius.rSm,
            border: border == null ? null : Border.all(color: border),
          ),
          child: Row(
            mainAxisSize: expand ? MainAxisSize.max : MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              AnimatedSwitcher(
                duration: AppMotion.fast,
                transitionBuilder: (c, a) => ScaleTransition(
                    scale: a, child: FadeTransition(opacity: a, child: c)),
                child: loading
                    ? SizedBox(
                        key: const ValueKey('spin'),
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                            strokeWidth: 2.2, color: fg),
                      )
                    : icon != null
                        ? Icon(icon,
                            key: const ValueKey('icon'), size: 20, color: fg)
                        : const SizedBox(key: ValueKey('none')),
              ),
              if (loading || icon != null) const SizedBox(width: 10),
              Flexible(
                child: Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                      color: fg, fontSize: 15, fontWeight: FontWeight.w700),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
