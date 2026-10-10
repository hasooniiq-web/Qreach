import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/utils/phone.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/app_sheet.dart';
import '../../../shared/widgets/choice_field.dart';
import '../../vehicles/data/vehicle_options.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/skeleton.dart';
import '../data/profile_models.dart';
import 'profile_controller.dart';

IconData platformIcon(String type) => switch (type) {
      'phone' => Icons.phone_outlined,
      'whatsapp' => Icons.chat_outlined,
      'telegram' => Icons.send_outlined,
      'instagram' => Icons.camera_alt_outlined,
      'facebook' => Icons.facebook_outlined,
      'snapchat' => Icons.photo_camera_front_outlined,
      'tiktok' => Icons.music_note_outlined,
      'twitter' => Icons.alternate_email,
      'linkedin' => Icons.work_outline,
      'youtube' => Icons.play_circle_outline,
      'website' => Icons.language,
      'email' => Icons.mail_outline,
      _ => Icons.link,
    };

/// What a visitor who scans the owner's code can see. Everything starts
/// hidden — a visitor can only send a message — and the owner switches items
/// on one at a time. Basic can show the name, phone and an emergency contact;
/// adding other platforms (Instagram, WhatsApp...) is Plus. The server
/// enforces this; the app only mirrors it and offers the upgrade.
class VisibilityScreen extends ConsumerWidget {
  const VisibilityScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final async = ref.watch(profileProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.visibilityTitle)),
      body: async.when(
        loading: () => const SkeletonList(count: 4),
        error: (error, _) => ErrorState(
            error: error, onRetry: () => ref.invalidate(profileProvider)),
        data: (profile) => _VisibilityBody(profile: profile),
      ),
    );
  }
}

class _VisibilityBody extends ConsumerWidget {
  const _VisibilityBody({required this.profile});

  final ProfileModel profile;

  /// Runs a change, refreshes the profile, and shows the server's message on failure.
  Future<void> _run(BuildContext context, WidgetRef ref,
      Future<ProfileModel> Function() action) async {
    final messenger = ScaffoldMessenger.of(context);
    try {
      await action();
      ref.invalidate(profileProvider);
    } catch (e) {
      messenger
          .showSnackBar(SnackBar(content: Text(asApiException(e).message)));
    }
  }

  Future<void> _addMethod(BuildContext context, WidgetRef ref) async {
    final result = await showAppSheet<_NewMethod>(
      context,
      builder: (_) => _AddMethodSheet(platforms: profile.platforms),
    );
    if (result == null || !context.mounted) return;

    await _run(
        context,
        ref,
        () => ref.read(profileRepositoryProvider).addMethod(
              type: result.type,
              value: result.value,
              label: result.label,
              visible: result.visible,
            ));
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final repo = ref.read(profileRepositoryProvider);
    final titleStyle = Theme.of(context)
        .textTheme
        .titleMedium
        ?.copyWith(fontWeight: FontWeight.w800);

    final shown = [
      if (profile.showName) l10n.homeOwnerLabel,
      if (profile.showAvatar) l10n.visAvatarLabel,
      if (profile.phoneField.visible && profile.phoneField.canShow)
        l10n.phoneShownLabel,
      if (profile.emergency?.visible == true) l10n.emergencySection,
      for (final m in profile.methods)
        if (m.visible && m.canShow) m.label,
    ];

    return ListView(
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
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
                  style:
                      const TextStyle(color: AppColors.brandWhite, height: 1.4),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.xl),

        // Name
        AppCard(
          child: SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(l10n.visShowName,
                style: const TextStyle(fontWeight: FontWeight.w700)),
            subtitle: Text(
                profile.name?.isNotEmpty == true
                    ? '${profile.name} — ${l10n.visNameHint}'
                    : l10n.visNameHint,
                style: const TextStyle(fontSize: 12)),
            value: profile.showName,
            onChanged: (v) => _run(context, ref, () => repo.setShowName(v)),
          ),
        ),
        const SizedBox(height: AppSpacing.md),

        // Avatar
        AppCard(
          child: SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(l10n.visShowAvatar,
                style: const TextStyle(fontWeight: FontWeight.w700)),
            subtitle:
                Text(l10n.visAvatarHint, style: const TextStyle(fontSize: 12)),
            value: profile.showAvatar,
            onChanged: profile.hasAvatar
                ? (v) => _run(context, ref, () => repo.setShowAvatar(v))
                : null,
          ),
        ),
        const SizedBox(height: AppSpacing.md),

        // Phone (Basic)
        _PhoneCard(
          key: ValueKey('phone-${profile.phoneField.value}'),
          field: profile.phoneField,
          onSave: (value, visible) => _run(context, ref,
              () => repo.updatePhone(phone: value, visible: visible)),
        ),
        const SizedBox(height: AppSpacing.xl),

        // Emergency contact (Basic)
        Text(l10n.emergencySection, style: titleStyle),
        const SizedBox(height: 4),
        Text(l10n.emergencyHint,
            style: TextStyle(color: AppColors.ink500, fontSize: 13)),
        const SizedBox(height: AppSpacing.md),
        _EmergencyCard(
          key: ValueKey(
              'em-${profile.emergency?.phone}-${profile.emergency?.visible}'),
          emergency: profile.emergency,
          onSave: (name, relation, phone, visible) => _run(
              context,
              ref,
              () => repo.saveEmergency(
                  name: name,
                  relationship: relation,
                  phone: phone,
                  visible: visible)),
          onRemove: () => _run(context, ref, repo.removeEmergency),
        ),
        const SizedBox(height: AppSpacing.xl),

        // Extra contact methods (Plus)
        Text(l10n.methodsSection, style: titleStyle),
        const SizedBox(height: AppSpacing.md),
        if (!profile.canAddMethods)
          AppCard(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                          color: AppColors.brandYellow.withValues(alpha: 0.22),
                          shape: BoxShape.circle),
                      child: const Icon(Icons.lock_outline_rounded),
                    ),
                    const SizedBox(width: AppSpacing.md),
                    Expanded(
                      child: Text(l10n.methodsLockedTitle,
                          style: const TextStyle(
                              fontWeight: FontWeight.w800, fontSize: 16)),
                    ),
                  ],
                ),
                const SizedBox(height: AppSpacing.md),
                Text(l10n.methodsLockedBody,
                    style: TextStyle(
                        color: AppColors.ink600, fontSize: 13.5, height: 1.5)),
                const SizedBox(height: AppSpacing.md),
                // The platforms Plus unlocks, shown as small icons.
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    for (final type in const [
                      'instagram',
                      'facebook',
                      'snapchat',
                      'tiktok',
                      'whatsapp',
                    ])
                      Container(
                        width: 38,
                        height: 38,
                        decoration: BoxDecoration(
                            color: AppColors.ink100,
                            borderRadius: BorderRadius.circular(12)),
                        child: Icon(platformIcon(type),
                            size: 20, color: AppColors.ink700),
                      ),
                  ],
                ),
                const SizedBox(height: AppSpacing.lg),
                AppButton(
                  label: l10n.upgradeToPlus,
                  icon: Icons.workspace_premium_rounded,
                  style: AppButtonStyle.accent,
                  onPressed: () => context.push('/subscription'),
                ),
              ],
            ),
          ),
        for (final m in profile.methods) ...[
          AppCard(
            padding: const EdgeInsets.symmetric(
                horizontal: AppSpacing.lg, vertical: AppSpacing.sm),
            child: Row(
              children: [
                Icon(platformIcon(m.type), color: AppColors.ink700),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(m.label,
                          style: const TextStyle(fontWeight: FontWeight.w700)),
                      Text(m.value,
                          textDirection: TextDirection.ltr,
                          style:
                              TextStyle(color: AppColors.ink500, fontSize: 12)),
                    ],
                  ),
                ),
                Switch(
                  value: m.visible && m.canShow,
                  onChanged: m.canShow
                      ? (v) => _run(
                          context, ref, () => repo.setMethodVisible(m.id, v))
                      : null,
                ),
                IconButton(
                  tooltip: l10n.removeMethod,
                  icon:
                      Icon(Icons.delete_outline, color: AppColors.statusDanger),
                  onPressed: () =>
                      _run(context, ref, () => repo.removeMethod(m.id)),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.sm),
        ],
        if (profile.canAddMethods) ...[
          if (profile.methods.isEmpty)
            Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.md),
              child: Text(l10n.methodsEmpty,
                  style: TextStyle(color: AppColors.ink500)),
            ),
          AppButton(
            label: l10n.addMethod,
            icon: Icons.add_rounded,
            style: AppButtonStyle.secondary,
            onPressed: () => _addMethod(context, ref),
          ),
        ],
        const SizedBox(height: AppSpacing.xl),
      ],
    );
  }
}

class _PhoneCard extends StatefulWidget {
  const _PhoneCard({super.key, required this.field, required this.onSave});

  final ContactField field;
  final void Function(String value, bool visible) onSave;

  @override
  State<_PhoneCard> createState() => _PhoneCardState();
}

class _PhoneCardState extends State<_PhoneCard> {
  late final _controller =
      TextEditingController(text: widget.field.value ?? '');

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  // Accepts a local Iraqi number with or without the leading trunk zero, or
  // one already written with a country code; falls back to the raw text
  // (unchanged behaviour) so an actually-invalid value still reaches the
  // server and shows its error message.
  String get _normalizedOrRaw =>
      normalizePhoneInput(_controller.text) ?? _controller.text.trim();

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final has = _controller.text.trim().isNotEmpty;

    return AppCard(
      child: Column(
        children: [
          TextField(
            controller: _controller,
            keyboardType: TextInputType.phone,
            textDirection: TextDirection.ltr,
            onChanged: (_) => setState(() {}),
            decoration: InputDecoration(
              labelText: l10n.phoneShownLabel,
              hintText: '07XXXXXXXXX',
              prefixIcon: const Icon(Icons.phone_outlined),
              suffixIcon: TextButton(
                onPressed: () =>
                    widget.onSave(_normalizedOrRaw, widget.field.visible),
                child: Text(l10n.save),
              ),
            ),
          ),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            value: widget.field.visible && widget.field.canShow,
            onChanged: has && widget.field.canShow
                ? (v) => widget.onSave(_normalizedOrRaw, v)
                : null,
            title: Text(l10n.showToVisitors),
          ),
        ],
      ),
    );
  }
}

class _EmergencyCard extends StatefulWidget {
  const _EmergencyCard({
    super.key,
    required this.emergency,
    required this.onSave,
    required this.onRemove,
  });

  final EmergencyModel? emergency;
  final void Function(String name, String? relation, String phone, bool visible)
      onSave;
  final VoidCallback onRemove;

  @override
  State<_EmergencyCard> createState() => _EmergencyCardState();
}

class _EmergencyCardState extends State<_EmergencyCard> {
  late final _name = TextEditingController(text: widget.emergency?.name ?? '');
  late String? _relation = (widget.emergency?.relationship ?? '').isEmpty
      ? null
      : widget.emergency!.relationship;
  late final _phone =
      TextEditingController(text: widget.emergency?.phone ?? '');
  final _form = GlobalKey<FormState>();

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    super.dispose();
  }

  void _save(bool visible) {
    if (!_form.currentState!.validate()) return;
    // Accepts a local Iraqi number with or without the leading trunk zero,
    // as well as one already written with a country code.
    final phone = normalizePhoneInput(_phone.text) ?? _phone.text.trim();
    widget.onSave(_name.text.trim(), (_relation ?? '').trim(), phone, visible);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final e = widget.emergency;

    return AppCard(
      child: Form(
        key: _form,
        child: Column(
          children: [
            TextFormField(
              controller: _name,
              decoration: InputDecoration(labelText: l10n.emergencyName),
              validator: (v) => (v == null || v.trim().length < 2)
                  ? l10n.requiredField
                  : null,
            ),
            const SizedBox(height: AppSpacing.md),
            // Father / mother / brother / ... with "Other" at the end for anything else.
            ChoiceField(
              label: l10n.emergencyRelation,
              value: _relation,
              options: relationshipOptions(
                  arabic: Localizations.localeOf(context).languageCode == 'ar'),
              onChanged: (v) => setState(() => _relation = v),
            ),
            const SizedBox(height: AppSpacing.md),
            TextFormField(
              controller: _phone,
              keyboardType: TextInputType.phone,
              textDirection: TextDirection.ltr,
              decoration: InputDecoration(
                  labelText: l10n.emergencyPhone, hintText: '07XXXXXXXXX'),
              validator: (v) => normalizePhoneInput(v ?? '') != null
                  ? null
                  : l10n.invalidPhone,
            ),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              value: e?.visible == true && e?.canShow == true,
              onChanged: e != null && e.canShow ? _save : null,
              title: Text(l10n.showToVisitors),
            ),
            Row(
              children: [
                Expanded(
                  child: AppButton(
                    label: l10n.save,
                    onPressed: () => _save(e?.visible ?? false),
                  ),
                ),
                if (e != null) ...[
                  const SizedBox(width: AppSpacing.sm),
                  IconButton(
                    tooltip: l10n.removeMethod,
                    onPressed: widget.onRemove,
                    icon: Icon(Icons.delete_outline,
                        color: AppColors.statusDanger),
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _NewMethod {
  const _NewMethod(this.type, this.value, this.label, this.visible);

  final String type;
  final String value;
  final String? label;
  final bool visible;
}

class _AddMethodSheet extends StatefulWidget {
  const _AddMethodSheet({required this.platforms});

  final List<PlatformInfo> platforms;

  @override
  State<_AddMethodSheet> createState() => _AddMethodSheetState();
}

class _AddMethodSheetState extends State<_AddMethodSheet> {
  final _value = TextEditingController();
  final _label = TextEditingController();
  PlatformInfo? _platform;
  bool _visible = false;

  @override
  void dispose() {
    _value.dispose();
    _label.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final p = _platform;
    final ready = p != null &&
        _value.text.trim().isNotEmpty &&
        (p.type != 'other' || _label.text.trim().isNotEmpty);

    return Padding(
      padding: EdgeInsets.fromLTRB(AppSpacing.xl, 0, AppSpacing.xl,
          MediaQuery.of(context).viewInsets.bottom + AppSpacing.xl),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.addMethod,
                style:
                    const TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
            const SizedBox(height: AppSpacing.lg),
            Wrap(
              spacing: AppSpacing.sm,
              runSpacing: AppSpacing.sm,
              children: [
                for (final pl in widget.platforms)
                  ChoiceChip(
                    avatar: Icon(platformIcon(pl.type), size: 18),
                    label: Text(pl.label),
                    selected: p?.type == pl.type,
                    onSelected: (_) => setState(() => _platform = pl),
                  ),
              ],
            ),
            if (p != null) ...[
              const SizedBox(height: AppSpacing.lg),
              TextField(
                controller: _value,
                autofocus: true,
                textDirection: p.type == 'other' ? null : TextDirection.ltr,
                onChanged: (_) => setState(() {}),
                decoration: InputDecoration(
                    labelText: l10n.methodValue, hintText: p.hint),
              ),
              if (p.type == 'other') ...[
                const SizedBox(height: AppSpacing.md),
                TextField(
                  controller: _label,
                  maxLength: 40,
                  onChanged: (_) => setState(() {}),
                  decoration: InputDecoration(labelText: l10n.methodLabel),
                ),
              ],
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                value: _visible,
                onChanged: (v) => setState(() => _visible = v),
                title: Text(l10n.showToVisitors),
              ),
              AppButton(
                label: l10n.save,
                onPressed: ready
                    ? () => Navigator.of(context).pop(_NewMethod(p.type,
                        _value.text.trim(), _label.text.trim(), _visible))
                    : null,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
