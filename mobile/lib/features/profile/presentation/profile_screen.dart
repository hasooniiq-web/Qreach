import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../l10n/app_localizations.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../auth/presentation/logout_confirm.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final authState = ref.watch(authControllerProvider);
    final phone = authState is AuthAuthenticated ? authState.user.phone : '';

    return Scaffold(
      appBar: AppBar(title: Text(l10n.profile)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Center(
            child: Column(
              children: [
                const CircleAvatar(
                  radius: 36,
                  backgroundColor: AppColors.ink100,
                  child: Icon(Icons.person_outline,
                      size: 32, color: AppColors.ink600),
                ),
                const SizedBox(height: 12),
                Text(phone,
                    style: const TextStyle(
                        fontWeight: FontWeight.w600, fontSize: 16),
                    textDirection: TextDirection.ltr),
              ],
            ),
          ),
          const SizedBox(height: 32),
          Card(
            child: Column(
              children: [
                ListTile(
                  leading:
                      const Icon(Icons.logout, color: AppColors.statusDanger),
                  title: Text(l10n.logout,
                      style: const TextStyle(color: AppColors.statusDanger)),
                  onTap: () =>
                      confirmAndLogout(context, ref, allDevices: false),
                ),
                const Divider(height: 1),
                ListTile(
                  leading: const Icon(Icons.phone_android,
                      color: AppColors.statusDanger),
                  title: Text(l10n.logoutAllDevices,
                      style: const TextStyle(color: AppColors.statusDanger)),
                  onTap: () =>
                      confirmAndLogout(context, ref, allDevices: true),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
