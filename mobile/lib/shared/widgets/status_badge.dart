import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';

enum StatusTone { success, neutral, warning, danger }

/// The one status-communication pattern for the app (a 10%-opacity pill),
/// used for tag status, vehicle protection status, and anything else that
/// needs a short state label — see docs/UI_UX_DESIGN_SYSTEM_V2.md. Replaces
/// the vehicle-detail screen's former private `_StatusChip`, which only
/// understood tag status.
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
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: _color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style:
            TextStyle(color: _color, fontSize: 12, fontWeight: FontWeight.w500),
      ),
    );
  }
}
