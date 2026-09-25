import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/settings_group.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../auth/presentation/logout_confirm.dart';
import '../../subscription/presentation/subscription_controller.dart';

/// Grouped settings (Account / Security / Preferences) instead of a stack of
/// separate cards. Every row leads somewhere real or shows real state —
/// nothing here is a dead entry.
class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final authState = ref.watch(authControllerProvider);
    final phone = authState is AuthAuthenticated ? authState.user.phone : '';
    final subscription = ref.watch(subscriptionProvider).valueOrNull;
    final showsContacts = subscription?.plan.showsPublicContacts ?? false;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.profile)),
      body: ListView(
        padding: const EdgeInsets.all(AppSpacing.lg),
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
                const SizedBox(height: AppSpacing.md),
                Text(phone,
                    style: const TextStyle(
                        fontWeight: FontWeight.w600, fontSize: 16),
                    textDirection: TextDirection.ltr),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.xxl),
          SettingsGroup(
            title: l10n.settingsAccount,
            rows: [
              SettingsRow(
                icon: Icons.workspace_premium_outlined,
                label: l10n.subscriptionTitle,
                value: subscription?.plan.name,
                onTap: () => context.push('/subscription'),
              ),
              SettingsRow(
                icon: Icons.contact_phone_outlined,
                label: l10n.publicContactTitle,
                trailing: subscription == null
                    ? null
                    : Icon(
                        showsContacts ? Icons.chevron_left : Icons.lock_outline,
                        color: AppColors.ink300,
                        semanticLabel: showsContacts
                            ? null
                            : l10n.publicContactLockedTitle,
                      ),
                onTap: () => context.push('/public-contact'),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xl),
          SettingsGroup(
            title: l10n.settingsPreferences,
            rows: [
              SettingsRow(
                icon: Icons.notifications_outlined,
                label: l10n.notifications,
                onTap: () => context.push('/notifications'),
              ),
              SettingsRow(
                icon: Icons.language,
                label: l10n.language,
                value: l10n.languageName,
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xl),
          SettingsGroup(
            title: l10n.settingsSecurity,
            rows: [
              SettingsRow(
                icon: Icons.logout,
                label: l10n.logout,
                destructive: true,
                onTap: () => confirmAndLogout(context, ref, allDevices: false),
              ),
              SettingsRow(
                icon: Icons.phone_android,
                label: l10n.logoutAllDevices,
                destructive: true,
                onTap: () => confirmAndLogout(context, ref, allDevices: true),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
