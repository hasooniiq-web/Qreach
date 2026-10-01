import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_button.dart';
import 'auth_scaffold.dart';

/// The very first screen: create a new account, or log in to an existing
/// one. Both lead to the same phone + OTP mechanism underneath (there is no
/// password, and the server never reveals whether a phone number already has
/// an account) — this screen only changes which wording the next screen
/// shows, nothing about how the request is actually handled.
class AuthChoiceScreen extends StatelessWidget {
  const AuthChoiceScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return AuthScaffold(
      title: l10n.welcomeTitle,
      subtitle: l10n.welcomeSubtitle,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          AppButton(
            label: l10n.createAccountButton,
            onPressed: () => context.push('/login/phone', extra: true),
          ),
          const SizedBox(height: AppSpacing.md),
          AppButton(
            label: l10n.alreadyHaveAccount,
            style: AppButtonStyle.secondary,
            onPressed: () => context.push('/login/phone', extra: false),
          ),
          const SizedBox(height: AppSpacing.md),
          TextButton.icon(
            onPressed: () => context.push('/server'),
            icon: const Icon(Icons.dns_outlined, size: 18),
            label: Text(l10n.serverSetup),
          ),
        ],
      ),
    );
  }
}
