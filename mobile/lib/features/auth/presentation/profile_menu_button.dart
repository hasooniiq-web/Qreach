import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../l10n/app_localizations.dart';
import 'logout_confirm.dart';

enum _ProfileMenuAction { profile, logout }

/// The one profile-access affordance used across the app (home header, and
/// previously the vehicle list's app bar) — see
/// docs/UI_UX_DESIGN_SYSTEM_V2.md. Tapping it offers "profile" or "log out"
/// directly rather than forcing a trip through the profile screen first.
class ProfileMenuButton extends ConsumerWidget {
  const ProfileMenuButton({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;

    return PopupMenuButton<_ProfileMenuAction>(
      tooltip: l10n.profile,
      icon: CircleAvatar(
        radius: 16,
        backgroundColor: AppColors.ink100,
        child: Icon(Icons.person_outline, size: 18, color: AppColors.ink600),
      ),
      onSelected: (action) {
        switch (action) {
          case _ProfileMenuAction.profile:
            context.push('/profile');
          case _ProfileMenuAction.logout:
            confirmAndLogout(context, ref, allDevices: false);
        }
      },
      itemBuilder: (context) => [
        PopupMenuItem(
          value: _ProfileMenuAction.profile,
          child: ListTile(
            leading: const Icon(Icons.account_circle_outlined),
            title: Text(l10n.profile),
            contentPadding: EdgeInsets.zero,
          ),
        ),
        PopupMenuItem(
          value: _ProfileMenuAction.logout,
          child: ListTile(
            leading: Icon(Icons.logout, color: AppColors.statusDanger),
            title: Text(l10n.logout,
                style: TextStyle(color: AppColors.statusDanger)),
            contentPadding: EdgeInsets.zero,
          ),
        ),
      ],
    );
  }
}
