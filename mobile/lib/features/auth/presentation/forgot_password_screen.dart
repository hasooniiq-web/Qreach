import 'package:flutter/material.dart';
import 'auth_errors.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/utils/phone.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/error_banner.dart';
import 'auth_controller.dart';
import 'auth_scaffold.dart';

/// Sets a first-ever password (an account that pre-dates password login) or
/// resets a forgotten one — same OTP-then-act flow either way. Phase 1 sends
/// the OTP; phase 2 (revealed once that succeeds) takes the code and the new
/// password together and logs the customer in.
class ForgotPasswordScreen extends ConsumerStatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  ConsumerState<ForgotPasswordScreen> createState() =>
      _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends ConsumerState<ForgotPasswordScreen> {
  final _phoneFormKey = GlobalKey<FormState>();
  final _resetFormKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController();
  final _codeController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscure = true;
  bool _otpSent = false;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _phoneController.dispose();
    _codeController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  String get _fullPhone => toIraqiE164(_phoneController.text);

  Future<void> _sendOtp() async {
    if (!_phoneFormKey.currentState!.validate()) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await ref
          .read(authControllerProvider.notifier)
          .requestPasswordOtp(_fullPhone);
      if (mounted) setState(() => _otpSent = true);
    } catch (e) {
      setState(() => _error = authErrorMessage(AppLocalizations.of(context)!, asApiException(e)));
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<void> _submit() async {
    if (!_resetFormKey.currentState!.validate()) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await ref.read(authControllerProvider.notifier).setPassword(
            phone: _fullPhone,
            code: _codeController.text.trim(),
            password: _passwordController.text,
          );
      // Navigation happens automatically via the router's redirect once
      // AuthController's state flips to AuthAuthenticated.
    } catch (e) {
      setState(() => _error = authErrorMessage(AppLocalizations.of(context)!, asApiException(e)));
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return AuthScaffold(
      title: l10n.forgotPasswordTitle,
      subtitle: l10n.forgotPasswordSubtitle,
      showBack: true,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (_error != null) ErrorBanner(message: _error!),
          Form(
            key: _phoneFormKey,
            child: TextFormField(
              controller: _phoneController,
              enabled: !_otpSent,
              keyboardType: TextInputType.phone,
              textDirection: TextDirection.ltr,
              decoration: InputDecoration(
                labelText: l10n.phoneLabel,
                hintText: l10n.phoneHint,
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return l10n.requiredField;
                }
                if (!isValidIraqiLocalNumber(value)) {
                  return l10n.invalidPhone;
                }
                return null;
              },
            ),
          ),
          if (!_otpSent) ...[
            const SizedBox(height: AppSpacing.lg),
            AppButton(
              label: l10n.forgotPasswordSendOtp,
              loading: _submitting,
              onPressed: _submitting ? null : _sendOtp,
            ),
          ] else ...[
            const SizedBox(height: AppSpacing.md),
            Form(
              key: _resetFormKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  TextFormField(
                    controller: _codeController,
                    keyboardType: TextInputType.number,
                    textDirection: TextDirection.ltr,
                    maxLength: 6,
                    decoration: InputDecoration(
                        labelText: l10n.forgotPasswordCodeLabel),
                    validator: (value) => (value == null || value.length != 6)
                        ? l10n.requiredField
                        : null,
                  ),
                  TextFormField(
                    controller: _passwordController,
                    obscureText: _obscure,
                    decoration: InputDecoration(
                      labelText: l10n.forgotPasswordNewPasswordLabel,
                      hintText: l10n.passwordHint,
                      suffixIcon: IconButton(
                        icon: Icon(_obscure
                            ? Icons.visibility_outlined
                            : Icons.visibility_off_outlined),
                        onPressed: () => setState(() => _obscure = !_obscure),
                      ),
                    ),
                    validator: (value) => (value == null || value.length < 8)
                        ? l10n.passwordTooShort
                        : null,
                  ),
                  const SizedBox(height: AppSpacing.md),
                  AppButton(
                    label: l10n.forgotPasswordSubmit,
                    loading: _submitting,
                    onPressed: _submitting ? null : _submit,
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}
