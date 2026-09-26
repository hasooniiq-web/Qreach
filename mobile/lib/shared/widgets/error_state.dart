import 'package:flutter/material.dart';

import '../../core/design/motion.dart';
import '../../core/design/tokens.dart';
import '../../core/network/api_client.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_spacing.dart';
import '../../l10n/app_localizations.dart';
import 'app_button.dart';

/// Full-area error with a retry action. Never shows a raw exception — the
/// message comes from [ApiException] / generic copy — and always offers the
/// next step.
class ErrorState extends StatelessWidget {
  const ErrorState({super.key, required this.error, required this.onRetry});

  final Object error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final message = asApiException(error).message;

    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.xxl),
        child: FadeSlideIn(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 84,
                height: 84,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: AppColors.statusDanger.withValues(alpha: 0.12),
                ),
                child: Icon(Icons.wifi_off_rounded,
                    size: 38, color: AppColors.statusDanger),
              ),
              const SizedBox(height: AppSpacing.xl),
              Text(l10n.loadErrorTitle,
                  style: AppText.title(context), textAlign: TextAlign.center),
              const SizedBox(height: AppSpacing.sm),
              ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 300),
                child: Text(message,
                    style:
                        AppText.body(context).copyWith(color: AppColors.ink500),
                    textAlign: TextAlign.center),
              ),
              const SizedBox(height: AppSpacing.xl),
              AppButton(
                label: l10n.retry,
                icon: Icons.refresh_rounded,
                onPressed: onRetry,
                expand: false,
                style: AppButtonStyle.secondary,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
