import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/notification_bell.dart';
import '../../../shared/widgets/settings_group.dart';
import '../../auth/presentation/logout_confirm.dart';
import '../../subscription/presentation/subscription_controller.dart';
import '../data/profile_models.dart';
import 'profile_controller.dart';

/// Profile tab: who I am (name, number, plan), what visitors can see, my
/// subscription, and security. Every row leads somewhere real.
class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  Future<void> _editName(
      BuildContext context, WidgetRef ref, ProfileModel profile) async {
    final l10n = AppLocalizations.of(context)!;
    final controller = TextEditingController(text: profile.name ?? '');
    final formKey = GlobalKey<FormState>();

    final saved = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n.editName),
        content: Form(
          key: formKey,
          child: TextFormField(
            controller: controller,
            autofocus: true,
            maxLength: 100,
            decoration: InputDecoration(
                labelText: l10n.profileName, hintText: l10n.profileNameHint),
            validator: (v) =>
                (v == null || v.trim().length < 2) ? l10n.requiredField : null,
          ),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: Text(l10n.cancel)),
          TextButton(
            onPressed: () {
              if (formKey.currentState!.validate()) {
                Navigator.of(context).pop(controller.text.trim());
              }
            },
            child: Text(l10n.save),
          ),
        ],
      ),
    );
    controller.dispose();

    if (saved == null || !context.mounted) return;
    final messenger = ScaffoldMessenger.of(context);
    try {
      await ref.read(profileRepositoryProvider).updateName(saved);
      ref.invalidate(profileProvider);
      messenger.showSnackBar(SnackBar(content: Text(l10n.saved)));
    } catch (e) {
      messenger
          .showSnackBar(SnackBar(content: Text(asApiException(e).message)));
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final profile = ref.watch(profileProvider).valueOrNull;
    final subscription = ref.watch(subscriptionProvider).valueOrNull;

    final name = profile?.name;
    final initial =
        (name != null && name.isNotEmpty) ? name.characters.first : '؟';
    final sharing = profile?.sharesAnything ?? false;

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.tabProfile),
        actions: const [NotificationBell()],
      ),
      body: ListView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          Container(
            padding: const EdgeInsets.all(AppSpacing.xl),
            decoration: BoxDecoration(
              color: AppColors.brandBlack,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 32,
                  backgroundColor: AppColors.brandYellow,
                  child: Text(initial,
                      style: const TextStyle(
                          color: AppColors.brandBlack,
                          fontSize: 26,
                          fontWeight: FontWeight.w800)),
                ),
                const SizedBox(width: AppSpacing.lg),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        (name != null && name.isNotEmpty) ? name : l10n.notSet,
                        style: const TextStyle(
                            color: AppColors.brandWhite,
                            fontSize: 20,
                            fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 2),
                      Text(profile?.phone ?? '',
                          textDirection: TextDirection.ltr,
                          style: const TextStyle(color: Colors.white70)),
                      if (subscription != null) ...[
                        const SizedBox(height: AppSpacing.sm),
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 3),
                          decoration: BoxDecoration(
                            color: subscription.plan.showsPublicContacts
                                ? AppColors.brandYellow
                                : Colors.white24,
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(subscription.plan.name,
                              style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                  color: subscription.plan.showsPublicContacts
                                      ? AppColors.brandBlack
                                      : AppColors.brandWhite)),
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.xl),
          SettingsGroup(
            title: l10n.profileMyInfo,
            rows: [
              SettingsRow(
                icon: Icons.badge_outlined,
                label: l10n.profileName,
                value: (name != null && name.isNotEmpty) ? name : l10n.notSet,
                trailing: const Icon(Icons.edit_outlined,
                    size: 20, color: AppColors.ink400),
                onTap: profile == null
                    ? null
                    : () => _editName(context, ref, profile),
              ),
              SettingsRow(
                icon: Icons.visibility_outlined,
                label: l10n.visibilityTitle,
                value: sharing
                    ? l10n.visibilitySharing(_sharedItems(l10n, profile!))
                    : l10n.visibilityNoneShown,
                onTap: () => context.push('/visibility'),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xl),
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

  String _sharedItems(AppLocalizations l10n, ProfileModel p) => [
        if (p.phoneField.visible && p.phoneField.canShow)
          l10n.featurePublicPhone,
        if (p.instagram.visible && p.instagram.canShow) l10n.instagramLabel,
        if (p.extra.visible && p.extra.canShow) l10n.extraLabel,
      ].join('، ');
}
