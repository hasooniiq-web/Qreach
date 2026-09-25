import 'package:flutter/material.dart';

/// The one card recipe for the app — 12px radius, 1px `ink200` border, no
/// shadow (already the theme's `CardThemeData` default). Use this instead
/// of a bare `Card(...)` so every card in the app stays visually identical
/// without each screen re-specifying the same decoration by hand — see
/// docs/UI_UX_DESIGN_SYSTEM_V2.md.
class AppCard extends StatelessWidget {
  const AppCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.onTap,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final card = Card(
      margin: EdgeInsets.zero,
      child: Padding(padding: padding, child: child),
    );

    if (onTap == null) return card;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: card,
      ),
    );
  }
}
