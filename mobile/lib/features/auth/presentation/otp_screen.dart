import 'dart:async';
import 'auth_errors.dart';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/otp_boxes.dart';

import '../../../core/network/api_client.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/error_banner.dart';
import 'auth_controller.dart';
import 'auth_scaffold.dart';

/// Verifies the OTP sent by registration (AuthController.register) — the
/// only place an OTP is sent now, since logging in afterwards uses a
/// password instead. [name]/[password] aren't shown here; they're only
/// needed to resend (register() re-sends for whatever name/password were
/// typed, since it's the same upsert-then-send-OTP endpoint either way).
class OtpScreen extends ConsumerStatefulWidget {
  const OtpScreen({
    super.key,
    required this.name,
    required this.phone,
    required this.password,
  });

  final String name;
  final String phone;
  final String password;

  @override
  ConsumerState<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends ConsumerState<OtpScreen> {
  final _codeController = TextEditingController();
  bool _submitting = false;
  String? _error;
  int _cooldown = 60;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _startCooldown();
  }

  void _startCooldown() {
    _timer?.cancel();
    setState(() => _cooldown = 60);
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_cooldown <= 1) {
        timer.cancel();
        setState(() => _cooldown = 0);
      } else {
        setState(() => _cooldown--);
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _codeController.dispose();
    super.dispose();
  }

  Future<void> _verify() async {
    if (_codeController.text.trim().length != 6) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await ref.read(authControllerProvider.notifier).verifyRegister(
            phone: widget.phone,
            code: _codeController.text.trim(),
          );
      // Navigation happens automatically via the router's redirect once
      // AuthController's state flips to AuthAuthenticated.
    } catch (e) {
      setState(() => _error = authErrorMessage(AppLocalizations.of(context)!, asApiException(e)));
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<void> _resend() async {
    if (_cooldown > 0) return;
    try {
      await ref.read(authControllerProvider.notifier).register(
            name: widget.name,
            phone: widget.phone,
            password: widget.password,
          );
      _startCooldown();
    } catch (e) {
      setState(() => _error = authErrorMessage(AppLocalizations.of(context)!, asApiException(e)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return AuthScaffold(
      showBack: true,
      title: l10n.otpTitle,
      // LRE...PDF keeps the leading + at the left of the number on an RTL screen.
      subtitle: l10n.otpSubtitle('‪${widget.phone}‬'),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (_error != null) ErrorBanner(message: _error!),
          OtpBoxes(
            controller: _codeController,
            onChanged: (value) {
              if (value.length == 6) _verify();
            },
          ),
          const SizedBox(height: AppSpacing.lg),
          AppButton(
            label: l10n.verify,
            loading: _submitting,
            onPressed: _submitting ? null : _verify,
          ),
          const SizedBox(height: AppSpacing.md),
          Center(
            child: TextButton(
              onPressed: _cooldown > 0 ? null : _resend,
              child: Text(
                  _cooldown > 0 ? l10n.resendIn(_cooldown) : l10n.resendCode),
            ),
          ),
        ],
      ),
    );
  }
}
