import 'package:flutter/material.dart';

import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_spacing.dart';

/// A placeholder block with a soft light sweep — shown instead of a spinner so
/// the screen keeps its final shape while data loads. It stops sweeping when
/// the platform asks for reduced motion.
class SkeletonBox extends StatefulWidget {
  const SkeletonBox(
      {super.key, this.width, this.height = 16, this.radius = 10});

  final double? width;
  final double height;
  final double radius;

  @override
  State<SkeletonBox> createState() => _SkeletonBoxState();
}

class _SkeletonBoxState extends State<SkeletonBox>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1300),
  )..repeat();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final reduce = MediaQuery.of(context).disableAnimations;
    if (reduce && _controller.isAnimating) _controller.stop();

    final base = AppColors.ink100;
    final glow = AppColors.dark
        ? Colors.white.withValues(alpha: 0.06)
        : Colors.white.withValues(alpha: 0.75);

    return AnimatedBuilder(
      animation: _controller,
      builder: (context, _) {
        final t = reduce ? 0.5 : _controller.value;
        return Container(
          width: widget.width,
          height: widget.height,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(widget.radius),
            gradient: LinearGradient(
              begin: Alignment(-1.6 + 3.2 * t, 0),
              end: Alignment(-0.6 + 3.2 * t, 0),
              colors: [base, glow, base],
              stops: const [0.0, 0.5, 1.0],
            ),
          ),
        );
      },
    );
  }
}

/// Placeholder shaped like a list-row card.
class SkeletonCard extends StatelessWidget {
  const SkeletonCard({super.key, this.height = 88});

  final double height;

  @override
  Widget build(BuildContext context) {
    return SkeletonBox(height: height, radius: AppRadius.md);
  }
}

/// A column of [count] card placeholders with standard spacing.
class SkeletonList extends StatelessWidget {
  const SkeletonList({super.key, this.count = 3});

  final int count;

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.lg),
      itemCount: count,
      separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
      itemBuilder: (_, __) => const SkeletonCard(),
    );
  }
}

/// A list-row skeleton: avatar, two text lines, and a trailing stamp.
class SkeletonRow extends StatelessWidget {
  const SkeletonRow({super.key});

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 10),
      child: Row(
        children: [
          SkeletonBox(width: 44, height: 44, radius: 22),
          SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                SkeletonBox(width: 140, height: 14),
                SizedBox(height: 8),
                SkeletonBox(height: 12),
              ],
            ),
          ),
          SizedBox(width: AppSpacing.md),
          SkeletonBox(width: 32, height: 10),
        ],
      ),
    );
  }
}
