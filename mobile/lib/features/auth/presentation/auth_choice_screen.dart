import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_button.dart';
import 'auth_controller.dart';
import 'auth_scaffold.dart';

/// The very first screen: create a new account, or log in to an existing
/// one. Both lead to the same phone + OTP mechanism underneath (there is no
/// password, and the server never reveals whether a phone number already has
/// an account) — this screen only changes which wording the next screen
/// shows, nothing about how the request is actually handled.
class AuthChoiceScreen extends ConsumerWidget {
  const AuthChoiceScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final replaced = ref.watch(sessionReplacedNoticeProvider);

    void clearNotice() =>
        ref.read(sessionReplacedNoticeProvider.notifier).state = false;

    return AuthScaffold(
      title: l10n.welcomeTitle,
      subtitle: l10n.welcomeSubtitle,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (replaced) ...[
            // Shown once after the server ended this session because the
            // account was opened on another device.
            Container(
              key: const ValueKey('session-replaced-notice'),
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: AppColors.statusWarning.withValues(alpha: 0.16),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Row(
                children: [
                  Icon(Icons.devices_other_rounded,
                      color: AppColors.statusWarning),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: Text(l10n.sessionReplacedNotice,
                        style: TextStyle(
                            color: AppColors.ink900,
                            fontSize: 13.5,
                            fontWeight: FontWeight.w600)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.md),
          ],
          AppButton(
            label: l10n.createAccountButton,
            onPressed: () {
              clearNotice();
              context.push('/login/signup');
            },
          ),
          const SizedBox(height: AppSpacing.md),
          AppButton(
            label: l10n.alreadyHaveAccount,
            style: AppButtonStyle.secondary,
            onPressed: () {
              clearNotice();
              context.push('/login/password');
            },
          ),
        ],
      ),
    );
  }
}
