import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/error_banner.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/skeleton.dart';
import '../data/profile_models.dart';
import 'profile_controller.dart';

/// Where the owner adds their details and chooses, one by one, what a
/// visitor who scans their code can see. Everything starts hidden — a
/// visitor can only send a message. Showing a detail is a Pro feature: on
/// Basic the switches are locked (the server refuses them regardless).
class VisibilityScreen extends ConsumerWidget {
  const VisibilityScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final async = ref.watch(profileProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.visibilityTitle)),
      body: async.when(
        loading: () => const SkeletonList(count: 3),
        error: (error, _) => ErrorState(
            error: error, onRetry: () => ref.invalidate(profileProvider)),
        data: (profile) => _VisibilityForm(initial: profile),
      ),
    );
  }
}

class _VisibilityForm extends ConsumerStatefulWidget {
  const _VisibilityForm({required this.initial});

  final ProfileModel initial;

  @override
  ConsumerState<_VisibilityForm> createState() => _VisibilityFormState();
}

class _VisibilityFormState extends ConsumerState<_VisibilityForm> {
  final _formKey = GlobalKey<FormState>();
  late final _phone =
      TextEditingController(text: widget.initial.phoneField.value ?? '');
  late final _instagram =
      TextEditingController(text: widget.initial.instagram.value ?? '');
  late final _extra =
      TextEditingController(text: widget.initial.extra.value ?? '');
  late bool _phoneVisible = widget.initial.phoneField.visible;
  late bool _instagramVisible = widget.initial.instagram.visible;
  late bool _extraVisible = widget.initial.extra.visible;
  bool _saving = false;
  String? _error;

  bool get _canShow => widget.initial.phoneField.canShow;

  @override
  void dispose() {
    _phone.dispose();
    _instagram.dispose();
    _extra.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    final l10n = AppLocalizations.of(context)!;
    final messenger = ScaffoldMessenger.of(context);

    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      await ref.read(profileRepositoryProvider).updateContact(
            phone: _phone.text.trim(),
            phoneVisible: _phoneVisible && _phone.text.trim().isNotEmpty,
            instagram: _instagram.text.trim().replaceFirst('@', ''),
            instagramVisible:
                _instagramVisible && _instagram.text.trim().isNotEmpty,
            extra: _extra.text.trim(),
            extraVisible: _extraVisible && _extra.text.trim().isNotEmpty,
          );
      ref.invalidate(profileProvider);
      messenger.showSnackBar(SnackBar(content: Text(l10n.saved)));
    } catch (e) {
      setState(() => _error = asApiException(e).message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final shown = [
      if (_phoneVisible && _phone.text.trim().isNotEmpty)
        l10n.featurePublicPhone,
      if (_instagramVisible && _instagram.text.trim().isNotEmpty)
        l10n.instagramLabel,
      if (_extraVisible && _extra.text.trim().isNotEmpty) l10n.extraLabel,
    ];

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          Text(l10n.visibilitySubtitle,
              style: const TextStyle(color: AppColors.ink500, height: 1.5)),
          const SizedBox(height: AppSpacing.lg),
          // What a visitor will actually see, stated plainly.
          Container(
            padding: const EdgeInsets.all(AppSpacing.lg),
            decoration: BoxDecoration(
              color: AppColors.brandBlack,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Row(
              children: [
                Icon(
                    shown.isEmpty
                        ? Icons.visibility_off_outlined
                        : Icons.visibility_outlined,
                    color: AppColors.brandYellow),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Text(
                    shown.isEmpty
                        ? l10n.visibilityNoneShown
                        : l10n.visibilitySharing(shown.join('، ')),
                    style: const TextStyle(
                        color: AppColors.brandWhite, height: 1.4),
                  ),
                ),
              ],
            ),
          ),
          if (!_canShow) ...[
            const SizedBox(height: AppSpacing.md),
            AppCard(
              onTap: () => context.push('/subscription'),
              child: Row(
                children: [
                  const Icon(Icons.lock_outline),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(child: Text(l10n.visibilityLockedHint)),
                  Text(l10n.viewPlans,
                      style: const TextStyle(fontWeight: FontWeight.w700)),
                ],
              ),
            ),
          ],
          const SizedBox(height: AppSpacing.xl),
          if (_error != null) ErrorBanner(message: _error!),
          _FieldCard(
            icon: Icons.phone_outlined,
            controller: _phone,
            label: l10n.phoneShownLabel,
            hint: '+9647XXXXXXXXX',
            ltr: true,
            keyboardType: TextInputType.phone,
            visible: _phoneVisible,
            enabled: _canShow,
            onVisible: (v) => setState(() => _phoneVisible = v),
            onChanged: () => setState(() {}),
            validator: (v) {
              final t = v?.trim() ?? '';
              if (t.isEmpty) return null;
              return RegExp(r'^\+?[1-9]\d{7,14}$').hasMatch(t)
                  ? null
                  : l10n.invalidPhone;
            },
          ),
          const SizedBox(height: AppSpacing.md),
          _FieldCard(
            icon: Icons.alternate_email,
            controller: _instagram,
            label: l10n.instagramLabel,
            hint: l10n.instagramHint,
            ltr: true,
            visible: _instagramVisible,
            enabled: _canShow,
            onVisible: (v) => setState(() => _instagramVisible = v),
            onChanged: () => setState(() {}),
            validator: (v) {
              final t = (v?.trim() ?? '').replaceFirst('@', '');
              if (t.isEmpty) return null;
              return RegExp(r'^[A-Za-z0-9._]{1,30}$').hasMatch(t)
                  ? null
                  : l10n.invalidInstagram;
            },
          ),
          const SizedBox(height: AppSpacing.md),
          _FieldCard(
            icon: Icons.info_outline,
            controller: _extra,
            label: l10n.extraLabel,
            hint: l10n.extraHint,
            maxLength: 200,
            visible: _extraVisible,
            enabled: _canShow,
            onVisible: (v) => setState(() => _extraVisible = v),
            onChanged: () => setState(() {}),
          ),
          const SizedBox(height: AppSpacing.xl),
          ElevatedButton(
            onPressed: _saving ? null : _save,
            child: _saving
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                        strokeWidth: 2, color: AppColors.brandWhite))
                : Text(l10n.save),
          ),
        ],
      ),
    );
  }
}

class _FieldCard extends StatelessWidget {
  const _FieldCard({
    required this.icon,
    required this.controller,
    required this.label,
    required this.hint,
    required this.visible,
    required this.enabled,
    required this.onVisible,
    required this.onChanged,
    this.validator,
    this.ltr = false,
    this.maxLength,
    this.keyboardType,
  });

  final IconData icon;
  final TextEditingController controller;
  final String label;
  final String hint;
  final bool visible;
  final bool enabled;
  final ValueChanged<bool> onVisible;
  final VoidCallback onChanged;
  final FormFieldValidator<String>? validator;
  final bool ltr;
  final int? maxLength;
  final TextInputType? keyboardType;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return AppCard(
      child: Column(
        children: [
          TextFormField(
            controller: controller,
            keyboardType: keyboardType,
            textDirection: ltr ? TextDirection.ltr : null,
            maxLength: maxLength,
            onChanged: (_) => onChanged(),
            validator: validator,
            decoration: InputDecoration(
              labelText: label,
              hintText: hint,
              prefixIcon: Icon(icon),
            ),
          ),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            value: visible && enabled,
            onChanged: enabled ? onVisible : null,
            title: Text(l10n.showToVisitors),
            secondary: enabled
                ? null
                : const Icon(Icons.lock_outline, color: AppColors.ink400),
          ),
        ],
      ),
    );
  }
}
