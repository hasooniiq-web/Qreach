import 'package:flutter/material.dart';

import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';

/// The Qreach mark: a yellow rounded tile holding a QR glyph. Pops in with a
/// small overshoot the first time it appears.
class BrandMark extends StatelessWidget {
  const BrandMark({super.key, this.size = 64, this.animate = true});

  final double size;
  final bool animate;

  @override
  Widget build(BuildContext context) {
    final mark = Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: AppColors.brandYellow,
        borderRadius: BorderRadius.circular(size * 0.32),
        boxShadow: [
          BoxShadow(
              color: AppColors.brandYellow.withValues(alpha: 0.35),
              blurRadius: size * 0.5,
              offset: Offset(0, size * 0.12)),
        ],
      ),
      child: Icon(Icons.qr_code_2_rounded,
          size: size * 0.62, color: AppColors.brandBlack),
    );

    if (!animate || MediaQuery.of(context).disableAnimations) return mark;

    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0.6, end: 1),
      duration: AppMotion.slow,
      curve: AppMotion.settle,
      child: mark,
      builder: (context, t, child) => Opacity(
        opacity: ((t - 0.6) / 0.4).clamp(0.0, 1.0),
        child: Transform.scale(scale: t, child: child),
      ),
    );
  }
}
