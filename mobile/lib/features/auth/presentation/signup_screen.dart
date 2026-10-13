import 'package:flutter/material.dart';
import 'auth_errors.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/utils/phone.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/error_banner.dart';
import 'auth_controller.dart';
import 'auth_scaffold.dart';

/// Create a new account: name, phone, password. The phone is verified once,
/// right after this, with a one-time OTP (see OtpScreen) — logging in
/// afterwards never needs one again.
class SignupScreen extends ConsumerStatefulWidget {
  const SignupScreen({super.key});

  @override
  ConsumerState<SignupScreen> createState() => _SignupScreenState();
}

class _SignupScreenState extends ConsumerState<SignupScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscure = true;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  // Accepts either "7701234567" or the locally-dialed "07701234567" — the
  // leading trunk zero is stripped before the fixed +964 prefix is added.
  String get _fullPhone => toIraqiE164(_phoneController.text);

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    final name = _nameController.text.trim();
    final password = _passwordController.text;

    try {
      await ref.read(authControllerProvider.notifier).register(
            name: name,
            phone: _fullPhone,
            password: password,
          );
      if (!mounted) return;
      context.push('/login/signup/otp',
          extra: (name: name, phone: _fullPhone, password: password));
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
      title: l10n.createAccountTitle,
      subtitle: l10n.createAccountSubtitle,
      showBack: true,
      child: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (_error != null) ErrorBanner(message: _error!),
            TextFormField(
              controller: _nameController,
              textCapitalization: TextCapitalization.words,
              decoration: InputDecoration(
                labelText: l10n.nameLabel,
                hintText: l10n.nameHint,
              ),
              validator: (value) => (value == null || value.trim().length < 2)
                  ? l10n.requiredField
                  : null,
            ),
            const SizedBox(height: AppSpacing.md),
            TextFormField(
              controller: _phoneController,
              keyboardType: TextInputType.phone,
              textDirection: TextDirection.ltr,
              // No "+964" shown anywhere: the system is Iraq-only, so the
              // field just takes the local number as people actually type it
              // (with or without the leading zero) — toIraqiE164 adds the
              // country code internally when the request is sent.
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
            const SizedBox(height: AppSpacing.lg),
            AppButton(
              label: l10n.createAccountSubmit,
              loading: _submitting,
              onPressed: _submitting ? null : _submit,
            ),
          ],
        ),
      ),
    );
  }
}
