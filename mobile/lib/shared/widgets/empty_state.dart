import 'package:flutter/material.dart';

import '../../core/design/motion.dart';
import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';

/// A calm, friendly "nothing here yet": a soft illustration tile that gently
/// breathes, a short title, one line of guidance, and an optional action.
class EmptyState extends StatefulWidget {
  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    required this.subtitle,
    this.action,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final Widget? action;

  @override
  State<EmptyState> createState() => _EmptyStateState();
}

class _EmptyStateState extends State<EmptyState>
    with SingleTickerProviderStateMixin {
  late final AnimationController _breath = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 2400),
  )..repeat(reverse: true, count: 4);

  @override
  void dispose() {
    _breath.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final reduce = MediaQuery.of(context).disableAnimations;
    if (reduce && _breath.isAnimating) _breath.stop();

    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(32),
        child: FadeSlideIn(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              AnimatedBuilder(
                animation: _breath,
                builder: (context, child) {
                  final t =
                      Curves.easeInOut.transform(reduce ? 0 : _breath.value);
                  return SizedBox(
                    width: 132,
                    height: 132,
                    child: Stack(
                      alignment: Alignment.center,
                      children: [
                        Container(
                          width: 132 - 14 * (1 - t),
                          height: 132 - 14 * (1 - t),
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: AppColors.brandYellow
                                .withValues(alpha: 0.10 + 0.05 * t),
                          ),
                        ),
                        Container(
                          width: 92,
                          height: 92,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: AppColors.brandYellow.withValues(alpha: 0.9),
                          ),
                          child: child,
                        ),
                      ],
                    ),
                  );
                },
                child: Icon(widget.icon, size: 44, color: AppColors.brandBlack),
              ),
              const SizedBox(height: 24),
              Text(widget.title,
                  style: AppText.title(context), textAlign: TextAlign.center),
              const SizedBox(height: 8),
              ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 300),
                child: Text(widget.subtitle,
                    style:
                        AppText.body(context).copyWith(color: AppColors.ink500),
                    textAlign: TextAlign.center),
              ),
              if (widget.action != null) ...[
                const SizedBox(height: 24),
                widget.action!,
              ],
            ],
          ),
        ),
      ),
    );
  }
}
