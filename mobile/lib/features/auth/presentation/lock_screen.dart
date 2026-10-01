import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/brand_mark.dart';
import 'auth_controller.dart';

/// Shown when a session is already stored but the owner switched on
/// Face ID/fingerprint unlock — prompts automatically on arrival, with a
/// manual retry button if it's dismissed or fails.
class LockScreen extends ConsumerStatefulWidget {
  const LockScreen({super.key});

  @override
  ConsumerState<LockScreen> createState() => _LockScreenState();
}

class _LockScreenState extends ConsumerState<LockScreen> {
  bool _failed = false;
  bool _checking = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _unlock());
  }

  Future<void> _unlock() async {
    if (_checking) return;
    setState(() {
      _checking = true;
      _failed = false;
    });

    final l10n = AppLocalizations.of(context)!;
    final ok = await ref
        .read(authControllerProvider.notifier)
        .unlockWithBiometrics(l10n.lockScreenSubtitle);

    if (mounted) setState(() => _checking = false);
    if (!ok && mounted) setState(() => _failed = true);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      backgroundColor: AppColors.brandBlack,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.xl),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const BrandMark(size: 60),
              const SizedBox(height: AppSpacing.xxl),
              const Icon(Icons.fingerprint,
                  size: 72, color: AppColors.brandYellow),
              const SizedBox(height: AppSpacing.lg),
              Text(l10n.lockScreenTitle,
                  style: const TextStyle(
                      color: AppColors.brandWhite,
                      fontSize: 24,
                      fontWeight: FontWeight.w800)),
              const SizedBox(height: AppSpacing.sm),
              Text(
                _failed ? l10n.lockScreenFailed : l10n.lockScreenSubtitle,
                textAlign: TextAlign.center,
                style: TextStyle(
                    color: _failed ? AppColors.statusDanger : Colors.white70,
                    fontSize: 14,
                    height: 1.5),
              ),
              const SizedBox(height: AppSpacing.xxl),
              AppButton(
                label: l10n.lockScreenUnlockButton,
                loading: _checking,
                onPressed: _checking ? null : _unlock,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
