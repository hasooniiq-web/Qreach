import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../l10n/app_localizations.dart';
import 'auth_controller.dart';

Future<void> confirmAndLogout(
  BuildContext context,
  WidgetRef ref, {
  required bool allDevices,
}) async {
  final l10n = AppLocalizations.of(context)!;
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (context) => AlertDialog(
      content: Text(l10n.logoutConfirm),
      actions: [
        TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: Text(l10n.cancel)),
        TextButton(
          onPressed: () => Navigator.of(context).pop(true),
          child: Text(l10n.logout,
              style: TextStyle(color: AppColors.statusDanger)),
        ),
      ],
    ),
  );

  if (confirmed == true) {
    final controller = ref.read(authControllerProvider.notifier);
    if (allDevices) {
      await controller.logoutAllDevices();
    } else {
      await controller.logout();
    }
  }
}
