import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'tokens.dart';

bool _reduced(BuildContext context) => MediaQuery.of(context).disableAnimations;

/// Fades a child in while sliding it a short way from below. Use it for the
/// entrance of a screen's sections; pass an increasing [index] to stagger
/// siblings. It plays once, when the widget first appears, and does nothing
/// when the system asks for reduced motion.
class FadeSlideIn extends StatelessWidget {
  const FadeSlideIn({
    super.key,
    required this.child,
    this.index = 0,
    this.offset = 18,
    this.duration = AppMotion.slow,
    this.horizontal = false,
  });

  final Widget child;

  /// Position in a staggered group (0 = first, no delay).
  final int index;

  /// Distance travelled, in logical pixels.
  final double offset;
  final Duration duration;

  /// Slide in from the reading-start side instead of from below.
  final bool horizontal;

  @override
  Widget build(BuildContext context) {
    if (_reduced(context)) return child;

    final delay = AppMotion.stagger * index.clamp(0, 8);
    final total = duration + delay;
    final begin = delay.inMilliseconds / total.inMilliseconds;
    final rtl = Directionality.of(context) == TextDirection.rtl;

    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: total,
      curve: Interval(begin, 1, curve: AppMotion.enter),
      child: child,
      builder: (context, t, child) => Opacity(
        opacity: t,
        child: Transform.translate(
          offset: horizontal
              ? Offset((rtl ? 1 : -1) * -(1 - t) * offset, 0)
              : Offset(0, (1 - t) * offset),
          child: child,
        ),
      ),
    );
  }
}

/// Wraps a tappable surface with a press-down scale and an optional light
/// haptic, so every tap gives immediate physical feedback.
class Pressable extends StatefulWidget {
  const Pressable({
    super.key,
    required this.child,
    required this.onTap,
    this.scale = 0.97,
    this.haptic = false,
    this.borderRadius,
  });

  final Widget child;
  final VoidCallback? onTap;
  final double scale;
  final bool haptic;
  final BorderRadius? borderRadius;

  @override
  State<Pressable> createState() => _PressableState();
}

class _PressableState extends State<Pressable> {
  bool _down = false;

  void _set(bool v) {
    if (widget.onTap == null || _down == v) return;
    setState(() => _down = v);
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTapDown: (_) => _set(true),
      onTapCancel: () => _set(false),
      onTapUp: (_) => _set(false),
      onTap: widget.onTap == null
          ? null
          : () {
              if (widget.haptic) HapticFeedback.selectionClick();
              widget.onTap!();
            },
      child: AnimatedScale(
        scale: _down && !_reduced(context) ? widget.scale : 1,
        duration: AppMotion.fast,
        curve: AppMotion.enter,
        child: widget.child,
      ),
    );
  }
}

/// Counts a number up (or down) to [value] instead of snapping to it.
class AnimatedCount extends StatelessWidget {
  const AnimatedCount({super.key, required this.value, this.style});

  final int value;
  final TextStyle? style;

  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween(end: value.toDouble()),
      duration: _reduced(context) ? Duration.zero : AppMotion.slow,
      curve: AppMotion.enter,
      builder: (context, v, _) => Text('${v.round()}', style: style),
    );
  }
}

/// Cross-fades between states of one region (loading -> content, etc.).
class SwapFade extends StatelessWidget {
  const SwapFade({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return AnimatedSwitcher(
      duration: _reduced(context) ? Duration.zero : AppMotion.base,
      switchInCurve: AppMotion.enter,
      switchOutCurve: Curves.easeIn,
      transitionBuilder: (child, animation) =>
          FadeTransition(opacity: animation, child: child),
      child: child,
    );
  }
}
