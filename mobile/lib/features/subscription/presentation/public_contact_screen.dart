import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/error_banner.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/skeleton.dart';
import '../data/subscription_models.dart';
import 'subscription_controller.dart';

/// The details an owner chooses to show anyone who scans their tag. Editing
/// is a Pro entitlement decided by the server (`editable`); on Basic this
/// screen explains the limit instead of showing a form that would be refused.
class PublicContactScreen extends ConsumerWidget {
  const PublicContactScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final async = ref.watch(publicContactProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.publicContactTitle)),
      body: async.when(
        loading: () => const SkeletonList(count: 3),
        error: (error, _) => ErrorState(
            error: error, onRetry: () => ref.invalidate(publicContactProvider)),
        data: (contact) => contact.editable
            ? _PublicContactForm(initial: contact)
            : EmptyState(
                icon: Icons.lock_outline,
                title: l10n.publicContactLockedTitle,
                subtitle: l10n.publicContactLockedBody,
                action: ElevatedButton(
                  onPressed: () => context.push('/subscription'),
                  style: ElevatedButton.styleFrom(
                      minimumSize: const Size(200, 52)),
                  child: Text(l10n.viewPlans),
                ),
              ),
      ),
    );
  }
}

class _PublicContactForm extends ConsumerStatefulWidget {
  const _PublicContactForm({required this.initial});

  final PublicContactModel initial;

  @override
  ConsumerState<_PublicContactForm> createState() => _PublicContactFormState();
}

class _PublicContactFormState extends ConsumerState<_PublicContactForm> {
  final _formKey = GlobalKey<FormState>();
  late final _phone = TextEditingController(text: widget.initial.phone ?? '');
  late final _instagram =
      TextEditingController(text: widget.initial.instagram ?? '');
  late final _extra = TextEditingController(text: widget.initial.extra ?? '');
  bool _saving = false;
  String? _error;

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
      await ref.read(subscriptionRepositoryProvider).updatePublicContact(
            phone: _phone.text.trim(),
            instagram: _instagram.text.trim().replaceFirst('@', ''),
            extra: _extra.text.trim(),
          );
      ref.invalidate(publicContactProvider);
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

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          Text(l10n.publicContactSubtitle,
              style: const TextStyle(color: AppColors.ink500)),
          const SizedBox(height: AppSpacing.xl),
          if (_error != null) ErrorBanner(message: _error!),
          TextFormField(
            controller: _phone,
            keyboardType: TextInputType.phone,
            textDirection: TextDirection.ltr,
            decoration: InputDecoration(
              labelText: l10n.phoneShownLabel,
              hintText: '+9647XXXXXXXXX',
              prefixIcon: const Icon(Icons.phone_outlined),
            ),
            validator: (value) {
              final v = value?.trim() ?? '';
              if (v.isEmpty) return null;
              return RegExp(r'^\+?[1-9]\d{7,14}$').hasMatch(v)
                  ? null
                  : l10n.invalidPhone;
            },
          ),
          const SizedBox(height: AppSpacing.lg),
          TextFormField(
            controller: _instagram,
            textDirection: TextDirection.ltr,
            decoration: InputDecoration(
              labelText: l10n.instagramLabel,
              hintText: l10n.instagramHint,
              prefixText: '@',
              prefixIcon: const Icon(Icons.alternate_email),
            ),
            validator: (value) {
              final v = (value?.trim() ?? '').replaceFirst('@', '');
              if (v.isEmpty) return null;
              return RegExp(r'^[A-Za-z0-9._]{1,30}$').hasMatch(v)
                  ? null
                  : l10n.invalidInstagram;
            },
          ),
          const SizedBox(height: AppSpacing.lg),
          TextFormField(
            controller: _extra,
            maxLength: 200,
            maxLines: 2,
            decoration: InputDecoration(
              labelText: l10n.extraLabel,
              hintText: l10n.extraHint,
              prefixIcon: const Icon(Icons.info_outline),
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
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
