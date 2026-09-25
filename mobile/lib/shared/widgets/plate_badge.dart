import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';

/// Licence-plate style display of a plate number — the vehicle's visual
/// identity (white plate, black border, LTR digits even in an RTL screen).
class PlateBadge extends StatelessWidget {
  const PlateBadge({super.key, required this.plate, this.onDark = false});

  final String plate;

  /// On a black surface the plate keeps its white fill but drops the border.
  final bool onDark;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
      decoration: BoxDecoration(
        color: AppColors.brandWhite,
        borderRadius: BorderRadius.circular(6),
        border:
            onDark ? null : Border.all(color: AppColors.brandBlack, width: 1.5),
      ),
      child: Text(
        plate,
        textDirection: TextDirection.ltr,
        style: const TextStyle(
          color: AppColors.brandBlack,
          fontWeight: FontWeight.w800,
          fontSize: 16,
          letterSpacing: 2,
        ),
      ),
    );
  }
}
