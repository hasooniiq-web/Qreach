import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';

enum StatusTone { success, neutral, warning, danger }

/// A short state label: a tinted pill with a leading dot. The dot means the
/// state is never conveyed by colour alone.
class StatusBadge extends StatelessWidget {
  const StatusBadge({super.key, required this.label, required this.tone});

  final String label;
  final StatusTone tone;

  Color get _color => switch (tone) {
        StatusTone.success => AppColors.statusSuccess,
        StatusTone.neutral => AppColors.ink500,
        StatusTone.warning => AppColors.statusWarning,
        StatusTone.danger => AppColors.statusDanger,
      };

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: _color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 6,
            height: 6,
            decoration: BoxDecoration(color: _color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
                color: _color, fontSize: 12, fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}
