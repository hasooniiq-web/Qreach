import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import 'tokens.dart';

/// Route transition used for every full-screen page: the new page fades in
/// while sliding a short distance from the reading-end side (so it works in
/// RTL and LTR), and the page beneath dims and drifts back slightly. Quick
/// (320ms) and interruptible by the back gesture.
CustomTransitionPage<T> appPage<T>({
  required LocalKey key,
  required Widget child,
  String? name,
}) {
  return CustomTransitionPage<T>(
    key: key,
    name: name,
    child: child,
    transitionDuration: const Duration(milliseconds: 320),
    reverseTransitionDuration: const Duration(milliseconds: 260),
    transitionsBuilder: (context, animation, secondary, child) {
      final rtl = Directionality.of(context) == TextDirection.rtl;
      final enter = CurvedAnimation(parent: animation, curve: AppMotion.enter);
      final back =
          CurvedAnimation(parent: secondary, curve: AppMotion.standard);

      return SlideTransition(
        position: Tween<Offset>(
          begin: Offset(rtl ? -0.08 : 0.08, 0),
          end: Offset.zero,
        ).animate(enter),
        child: FadeTransition(
          opacity: enter,
          // The page underneath softly recedes.
          child: FadeTransition(
            opacity: Tween<double>(begin: 1, end: 0.6).animate(back),
            child: child,
          ),
        ),
      );
    },
  );
}

/// Shorthand for a [GoRoute] builder that uses [appPage].
Page<dynamic> Function(BuildContext, GoRouterState) animatedPage(
  Widget Function(BuildContext, GoRouterState) builder,
) =>
    (context, state) => appPage(
          key: state.pageKey,
          name: state.name,
          child: builder(context, state),
        );
