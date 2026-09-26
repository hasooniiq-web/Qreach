import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/design/motion.dart';
import '../../../core/design/tokens.dart';
import '../../../core/locale/locale_controller.dart';
import '../../../core/theme/theme_controller.dart';
import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_sheet.dart';
import '../../../shared/widgets/screen_header.dart';
import '../../../shared/widgets/settings_group.dart';
import '../../../shared/widgets/user_avatar.dart';
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

  Future<void> _changePhoto(
      BuildContext context, WidgetRef ref, ProfileModel profile) async {
    final l10n = AppLocalizations.of(context)!;
    final choice = await showAppSheet<String>(
      context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: Text(l10n.takePhoto),
              onTap: () => Navigator.of(context).pop('camera'),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: Text(l10n.chooseFromGallery),
              onTap: () => Navigator.of(context).pop('gallery'),
            ),
            if (profile.hasAvatar)
              ListTile(
                leading:
                    Icon(Icons.delete_outline, color: AppColors.statusDanger),
                title: Text(l10n.removePhoto,
                    style: TextStyle(color: AppColors.statusDanger)),
                onTap: () => Navigator.of(context).pop('remove'),
              ),
          ],
        ),
      ),
    );
    if (choice == null || !context.mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    try {
      if (choice == 'remove') {
        await ref.read(profileRepositoryProvider).removeAvatar();
      } else {
        final picked = await ImagePicker().pickImage(
          source: choice == 'camera' ? ImageSource.camera : ImageSource.gallery,
          maxWidth: 1024,
          maxHeight: 1024,
          imageQuality: 85,
        );
        if (picked == null) return;
        await ref.read(profileRepositoryProvider).uploadAvatar(picked.path);
      }
      ref.invalidate(profileProvider);
      messenger.showSnackBar(SnackBar(content: Text(l10n.photoUpdated)));
    } catch (e) {
      messenger
          .showSnackBar(SnackBar(content: Text(asApiException(e).message)));
    }
  }

  Future<void> _chooseTheme(BuildContext context, WidgetRef ref) async {
    final l10n = AppLocalizations.of(context)!;
    final current = ref.read(themeModeProvider);
    final picked = await showAppSheet<ThemeMode>(
      context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.all(AppSpacing.md),
              child: Text(l10n.appearance,
                  style: const TextStyle(
                      fontWeight: FontWeight.w800, fontSize: 16)),
            ),
            for (final entry in {
              ThemeMode.system: (l10n.themeSystem, Icons.smartphone),
              ThemeMode.light: (l10n.themeLight, Icons.light_mode_outlined),
              ThemeMode.dark: (l10n.themeDark, Icons.dark_mode_outlined),
            }.entries)
              ListTile(
                leading: Icon(entry.value.$2),
                title: Text(entry.value.$1),
                trailing: entry.key == current
                    ? Icon(Icons.check_circle, color: AppColors.onSurface)
                    : null,
                onTap: () => Navigator.of(context).pop(entry.key),
              ),
          ],
        ),
      ),
    );
    if (picked != null) {
      await ref.read(themeModeProvider.notifier).setMode(picked);
    }
  }

  Future<void> _chooseLanguage(BuildContext context, WidgetRef ref) async {
    final l10n = AppLocalizations.of(context)!;
    final current = ref.read(localeProvider).languageCode;
    final picked = await showAppSheet<String>(
      context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.all(AppSpacing.md),
              child: Text(l10n.chooseLanguage,
                  style: const TextStyle(
                      fontWeight: FontWeight.w800, fontSize: 16)),
            ),
            for (final entry in {
              'ar': l10n.languageArabic,
              'en': l10n.languageEnglish
            }.entries)
              ListTile(
                title: Text(entry.value),
                trailing: entry.key == current
                    ? Icon(Icons.check_circle, color: AppColors.onSurface)
                    : null,
                onTap: () => Navigator.of(context).pop(entry.key),
              ),
          ],
        ),
      ),
    );
    if (picked != null) {
      await ref.read(localeProvider.notifier).setLocale(Locale(picked));
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final profile = ref.watch(profileProvider).valueOrNull;
    final subscription = ref.watch(subscriptionProvider).valueOrNull;

    final name = profile?.name;
    final sharing = profile?.sharesAnything ?? false;

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: ListView(
          padding: const EdgeInsets.only(bottom: 120),
          children: [
            FadeSlideIn(child: ScreenHeader(title: l10n.tabProfile)),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: FadeSlideIn(
                index: 1,
                child: Container(
                  clipBehavior: Clip.antiAlias,
                  padding: const EdgeInsets.all(AppSpacing.xl),
                  decoration: BoxDecoration(
                    color: AppColors.brandBlack,
                    borderRadius: AppRadius.rLg,
                    boxShadow: AppShadows.raised,
                  ),
                  child: Row(
                    children: [
                      GestureDetector(
                        onTap: profile == null
                            ? null
                            : () => _changePhoto(context, ref, profile),
                        child: Stack(
                          clipBehavior: Clip.none,
                          children: [
                            UserAvatar(name: name, radius: 34),
                            PositionedDirectional(
                              bottom: -2,
                              end: -2,
                              child: Container(
                                padding: const EdgeInsets.all(5),
                                decoration: BoxDecoration(
                                  color: AppColors.surface,
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                      color: AppColors.onSurface, width: 1.5),
                                ),
                                child: Icon(Icons.photo_camera,
                                    size: 14, color: AppColors.onSurface),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: AppSpacing.lg),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              (name != null && name.isNotEmpty)
                                  ? name
                                  : l10n.notSet,
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
                                        color: subscription
                                                .plan.showsPublicContacts
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
                  trailing: Icon(Icons.edit_outlined,
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
                  onTap: () => _chooseLanguage(context, ref),
                ),
                SettingsRow(
                  icon: Icons.brightness_6_outlined,
                  label: l10n.appearance,
                  value: switch (ref.watch(themeModeProvider)) {
                    ThemeMode.light => l10n.themeLight,
                    ThemeMode.dark => l10n.themeDark,
                    ThemeMode.system => l10n.themeSystem,
                  },
                  onTap: () => _chooseTheme(context, ref),
                ),
                SettingsRow(
                  icon: Icons.dns_outlined,
                  label: l10n.serverSetup,
                  onTap: () => context.push('/server'),
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
                  onTap: () =>
                      confirmAndLogout(context, ref, allDevices: false),
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
      ),
    );
  }

  String _sharedItems(AppLocalizations l10n, ProfileModel p) => [
        if (p.phoneField.visible && p.phoneField.canShow)
          l10n.featurePublicPhone,
        if (p.showName) l10n.homeOwnerLabel,
        if (p.emergency?.visible == true) l10n.emergencySection,
        for (final m in p.methods)
          if (m.visible && m.canShow) m.label,
      ].join('، ');
}
