import 'package:flutter/material.dart';
import 'auth_errors.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/utils/phone.dart';
import '../../../core/utils/phone_input.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/error_banner.dart';
import 'auth_controller.dart';
import 'auth_scaffold.dart';

/// Phone + password. No OTP here — that only happens once, during
/// registration (see SignupScreen/OtpScreen) or a password (re)set
/// (ForgotPasswordScreen).
class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscure = true;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  String get _fullPhone => toIraqiE164(_phoneController.text);

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await ref.read(authControllerProvider.notifier).login(
            phone: _fullPhone,
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
      title: l10n.loginTitle,
      subtitle: l10n.loginSubtitle,
      showBack: true,
      child: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (_error != null) ErrorBanner(message: _error!),
            TextFormField(
              controller: _phoneController,
              keyboardType: TextInputType.phone,
              inputFormatters: localPhoneFormatters,
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
            const SizedBox(height: AppSpacing.md),
            TextFormField(
              controller: _passwordController,
              obscureText: _obscure,
              decoration: InputDecoration(
                labelText: l10n.passwordLabel,
                suffixIcon: IconButton(
                  icon: Icon(_obscure
                      ? Icons.visibility_outlined
                      : Icons.visibility_off_outlined),
                  onPressed: () => setState(() => _obscure = !_obscure),
                ),
              ),
              validator: (value) =>
                  (value == null || value.isEmpty) ? l10n.requiredField : null,
            ),
            Align(
              alignment: AlignmentDirectional.centerEnd,
              child: TextButton(
                onPressed: () => context.push('/login/password/forgot'),
                child: Text(l10n.forgotPasswordLink),
              ),
            ),
            const SizedBox(height: AppSpacing.sm),
            AppButton(
              label: l10n.loginSubmit,
              loading: _submitting,
              onPressed: _submitting ? null : _submit,
            ),
          ],
        ),
      ),
    );
  }
}
