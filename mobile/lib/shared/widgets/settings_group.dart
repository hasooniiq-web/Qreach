import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_spacing.dart';

/// A titled group of rows sharing one card surface with dividers between
/// them — the "grouped settings, not dozens of separate cards" pattern.
class SettingsGroup extends StatelessWidget {
  const SettingsGroup({super.key, required this.title, required this.rows});

  final String title;
  final List<Widget> rows;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: AppSpacing.sm),
          child: Text(title,
              style: Theme.of(context).textTheme.labelLarge?.copyWith(
                  color: AppColors.ink500, fontWeight: FontWeight.w600)),
        ),
        Card(
          margin: EdgeInsets.zero,
          clipBehavior: Clip.antiAlias,
          child: Column(
            children: [
              for (var i = 0; i < rows.length; i++) ...[
                if (i > 0) const Divider(height: 1),
                rows[i],
              ],
            ],
          ),
        ),
      ],
    );
  }
}

class SettingsRow extends StatelessWidget {
  const SettingsRow({
    super.key,
    required this.icon,
    required this.label,
    this.value,
    this.trailing,
    this.onTap,
    this.destructive = false,
  });

  final IconData icon;
  final String label;

  /// Secondary text shown under the label.
  final String? value;
  final Widget? trailing;
  final VoidCallback? onTap;
  final bool destructive;

  @override
  Widget build(BuildContext context) {
    final color = destructive ? AppColors.statusDanger : AppColors.ink900;

    return ListTile(
      minTileHeight: 56,
      leading: Icon(icon, color: destructive ? color : AppColors.ink600),
      title: Text(label,
          style: TextStyle(color: color, fontWeight: FontWeight.w500)),
      subtitle: value == null
          ? null
          : Text(value!, style: TextStyle(color: AppColors.ink500)),
      trailing: trailing ??
          (onTap != null && !destructive
              ? Icon(Icons.chevron_left, color: AppColors.ink300)
              : null),
      onTap: onTap,
    );
  }
}
