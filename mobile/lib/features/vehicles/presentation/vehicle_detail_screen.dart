import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_sheet.dart';
import 'package:go_router/go_router.dart';
import '../../../shared/widgets/iraqi_plate.dart';
import '../../../shared/widgets/vehicle_thumb.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../core/design/motion.dart';
import '../../../core/design/tokens.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../tags/data/tag_model.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/skeleton.dart';
import '../../../shared/widgets/status_badge.dart';
import '../data/vehicle_model.dart';
import 'vehicle_controller.dart';
import 'vehicle_type_labels.dart';

class VehicleDetailScreen extends ConsumerWidget {
  const VehicleDetailScreen({super.key, required this.vehicleId});

  final String vehicleId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final vehicleAsync = ref.watch(vehicleDetailProvider(vehicleId));

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.vehicleDetails),
      ),
      body: vehicleAsync.when(
        loading: () => ListView(
          physics: const NeverScrollableScrollPhysics(),
          padding: const EdgeInsets.all(AppSpacing.lg),
          children: const [
            SkeletonBox(height: 250, radius: AppRadius.lg),
            SizedBox(height: AppSpacing.xl),
            SkeletonBox(width: 120, height: 18),
            SizedBox(height: AppSpacing.md),
            SkeletonBox(height: 96, radius: AppRadius.md),
          ],
        ),
        error: (error, _) => ErrorState(
          error: error,
          onRetry: () => ref.invalidate(vehicleDetailProvider(vehicleId)),
        ),
        data: (vehicle) => RefreshIndicator(
          onRefresh: () => ref.refresh(vehicleDetailProvider(vehicleId).future),
          child: ListView(
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: [
              if (vehicle.status == 'inactive') ...[
                FadeSlideIn(child: const _DisabledBanner()),
                const SizedBox(height: AppSpacing.md),
              ],
              if (vehicle.lockedByPlan) ...[
                FadeSlideIn(child: const _LockedByPlanBanner()),
                const SizedBox(height: AppSpacing.md),
              ],
              FadeSlideIn(child: _VehicleHero(vehicle: vehicle)),
              const SizedBox(height: AppSpacing.xl),
              FadeSlideIn(
                index: 1,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(l10n.vehicleCodeTitle,
                        style: AppText.section(context).copyWith(fontSize: 18)),
                    const SizedBox(height: AppSpacing.md),
                    _CodeSection(vehicle: vehicle, vehicleId: vehicleId),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              // Editing is always possible; pausing and deleting only make sense once a code is linked.
              FadeSlideIn(
                index: 2,
                child: _VehicleActions(
                  vehicle: vehicle,
                  vehicleId: vehicleId,
                  editOnly: !vehicle.tags.any((t) => t.status == 'assigned'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// A vehicle has exactly one code. With none linked the owner scans one;
/// with one linked they can remove it (after which a new one can be linked).
class _CodeSection extends ConsumerStatefulWidget {
  const _CodeSection({required this.vehicle, required this.vehicleId});

  final VehicleModel vehicle;
  final String vehicleId;

  @override
  ConsumerState<_CodeSection> createState() => _CodeSectionState();
}

class _CodeSectionState extends ConsumerState<_CodeSection> {
  bool _removing = false;
  bool _busy = false;

  Future<void> _relink() async {
    final l10n = AppLocalizations.of(context)!;
    setState(() => _busy = true);
    final messenger = ScaffoldMessenger.of(context);
    try {
      await ref.read(vehicleRepositoryProvider).relinkTag(widget.vehicleId);
      ref.invalidate(vehicleDetailProvider(widget.vehicleId));
      await ref.read(vehicleListControllerProvider.notifier).refresh();
      messenger.showSnackBar(SnackBar(content: Text(l10n.codeRelinked)));
    } catch (e) {
      messenger
          .showSnackBar(SnackBar(content: Text(asApiException(e).message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  /// QR and NFC are two doors into the same code: either can be closed.
  Future<void> _setChannels({required bool qr, required bool nfc}) async {
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busy = true);
    try {
      await ref
          .read(vehicleRepositoryProvider)
          .setChannels(widget.vehicleId, qr: qr, nfc: nfc);
      ref.invalidate(vehicleDetailProvider(widget.vehicleId));
    } catch (e) {
      messenger
          .showSnackBar(SnackBar(content: Text(asApiException(e).message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }
  Future<void> _remove() async {
    final l10n = AppLocalizations.of(context)!;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n.removeCodeConfirmTitle),
        content: Text(l10n.removeCodeConfirmBody),
        actions: [
          TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: Text(l10n.cancel)),
          TextButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: Text(l10n.removeCode,
                style: TextStyle(color: AppColors.statusDanger)),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    setState(() => _removing = true);
    final messenger = ScaffoldMessenger.of(context);
    try {
      await ref.read(vehicleRepositoryProvider).removeTag(widget.vehicleId);
      ref.invalidate(vehicleDetailProvider(widget.vehicleId));
      await ref.read(vehicleListControllerProvider.notifier).refresh();
      messenger.showSnackBar(SnackBar(content: Text(l10n.codeRemoved)));
    } catch (e) {
      messenger
          .showSnackBar(SnackBar(content: Text(asApiException(e).message)));
    } finally {
      if (mounted) setState(() => _removing = false);
    }
  }

  /// The code as a QR image plus the link stored in it — for both QR and NFC
  /// codes (an NFC tag holds the same link, so its QR is a working fallback).
  void _showQr(BuildContext context, TagModel tag) {
    final l10n = AppLocalizations.of(context)!;

    showAppSheet<void>(
      context,
      builder: (context) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(
              AppSpacing.xl, 0, AppSpacing.xl, AppSpacing.xl),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('${tagTypeLabel(l10n, tag.type)}  ·  ${tag.serialNumber}',
                  textDirection: TextDirection.ltr,
                  style: const TextStyle(
                      fontWeight: FontWeight.w800, fontSize: 16)),
              const SizedBox(height: AppSpacing.lg),
              Container(
                padding: const EdgeInsets.all(AppSpacing.md),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.ink200),
                ),
                child: QrImageView(
                  data: tag.publicUrl,
                  size: 240,
                  errorCorrectionLevel: QrErrorCorrectLevel.Q,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final tag =
        widget.vehicle.tags.where((t) => t.status == 'assigned').firstOrNull;

    if (tag == null) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          AppCard(
            padding: const EdgeInsets.all(AppSpacing.xl),
            child: Row(
              children: [
                Icon(Icons.qr_code_2, size: 40, color: AppColors.ink300),
                const SizedBox(width: AppSpacing.lg),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(l10n.noCodeYetTitle,
                          style: const TextStyle(fontWeight: FontWeight.w700)),
                      Text(l10n.noCodeYetSubtitle,
                          style: TextStyle(color: AppColors.ink500)),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          if (!widget.vehicle.lockedByPlan)
          AppButton(
            label: l10n.linkNewTag,
            icon: Icons.qr_code_scanner_rounded,
            style: AppButtonStyle.accent,
            onPressed: _busy
                ? null
                : () => context.push('/vehicles/${widget.vehicleId}/scan'),
          ),
          // The code that was taken off this vehicle last can simply be put back.
          if (widget.vehicle.previousTagSerial != null &&
              !widget.vehicle.lockedByPlan) ...[
            const SizedBox(height: AppSpacing.md),
            AppButton(
              label: l10n.relinkPreviousTag(widget.vehicle.previousTagSerial!),
              icon: Icons.link_rounded,
              style: AppButtonStyle.secondary,
              loading: _busy,
              onPressed: _busy ? null : _relink,
            ),
          ],
        ],
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        AppCard(
          child: Row(
            children: [
              Icon(
                  tag.type == 'nfc'
                      ? Icons.nfc
                      : (tag.type == 'code'
                          ? Icons.contactless_outlined
                          : Icons.qr_code_2),
                  size: 32,
                  color: AppColors.ink800),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(tag.serialNumber,
                        style: const TextStyle(fontWeight: FontWeight.w800),
                        textDirection: TextDirection.ltr),
                    Text(tagTypeLabel(l10n, tag.type),
                        style:
                            TextStyle(color: AppColors.ink500, fontSize: 12)),
                  ],
                ),
              ),
              StatusBadge(
                label: tagStatusLabel(context, tag.status),
                tone: StatusTone.success,
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        AppButton(
          label: l10n.showQr,
          icon: Icons.qr_code_2_rounded,
          style: AppButtonStyle.secondary,
          onPressed: () => _showQr(context, tag),
        ),
        const SizedBox(height: AppSpacing.md),
        // The two ways a visitor can open this code.
        AppCard(
          padding: const EdgeInsets.symmetric(
              horizontal: AppSpacing.lg, vertical: AppSpacing.sm),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Padding(
                padding: const EdgeInsets.only(top: AppSpacing.sm),
                child: Text(l10n.channelsTitle,
                    style: const TextStyle(fontWeight: FontWeight.w800)),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                secondary: const Icon(Icons.qr_code_2_rounded),
                title: Text(l10n.channelQr),
                value: tag.qrEnabled,
                onChanged: _busy
                    ? null
                    : (v) => _setChannels(qr: v, nfc: tag.nfcEnabled),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                secondary: const Icon(Icons.nfc_rounded),
                title: Text(l10n.channelNfc),
                value: tag.nfcEnabled,
                onChanged: _busy
                    ? null
                    : (v) => _setChannels(qr: tag.qrEnabled, nfc: v),
              ),
              Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                child: Text(
                  !tag.qrEnabled && !tag.nfcEnabled
                      ? l10n.channelsBothOff
                      : l10n.channelsHint,
                  style: TextStyle(
                      color: !tag.qrEnabled && !tag.nfcEnabled
                          ? AppColors.statusDanger
                          : AppColors.ink500,
                      fontSize: 12.5,
                      height: 1.5),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        Text(l10n.oneCodeNote,
            style: TextStyle(color: AppColors.ink500, fontSize: 13)),
        const SizedBox(height: AppSpacing.md),
        AppButton(
          label: l10n.removeCode,
          icon: Icons.delete_outline_rounded,
          style: AppButtonStyle.danger,
          loading: _removing,
          onPressed: _removing ? null : _remove,
        ),
      ],
    );
  }
}

class _VehicleHero extends StatelessWidget {
  const _VehicleHero({required this.vehicle});

  final VehicleModel vehicle;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final isProtected = vehicle.tags.any((t) => t.status == 'assigned');
    final facts = <String>[
      if (vehicle.make != null) vehicle.make!,
      if (vehicle.model != null) vehicle.model!,
      vehicleTypeLabel(context, vehicle.vehicleType),
      if (vehicle.year != null) '${vehicle.year}',
      if (vehicle.color != null) vehicle.color!,
    ];

    final statusColor =
        isProtected ? const Color(0xFF4CD264) : const Color(0xFFFF6B5E);

    // Compact and calmer than before: a soft gradient, one status pill, the name,
    // the plate beside a small car, and a single line of small detail chips.
    return Container(
      padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.lg),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: AlignmentDirectional.topEnd,
          end: AlignmentDirectional.bottomStart,
          colors: [Color(0xFF26262B), AppColors.brandBlack],
        ),
        borderRadius: AppRadius.rLg,
        border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
        boxShadow: AppShadows.raised,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                    isProtected
                        ? Icons.verified_user_rounded
                        : Icons.link_off_rounded,
                    size: 14,
                    color: statusColor),
                const SizedBox(width: 5),
                Text(
                  isProtected
                      ? l10n.vehicleProtected
                      : l10n.vehicleNotProtectedYet,
                  style: TextStyle(
                      color: statusColor,
                      fontSize: 12,
                      fontWeight: FontWeight.w700),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          // The name the owner gave it — nothing at all when there is none (the plate
          // is shown just below; it is not repeated here in the name's place).
          if (vehicle.nameOrNull != null) ...[
            Text(vehicle.nameOrNull!,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                    color: AppColors.brandWhite,
                    fontSize: 21,
                    fontWeight: FontWeight.w800)),
            const SizedBox(height: AppSpacing.md),
          ],
          Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Hero(
                tag: 'plate-${vehicle.id}',
                child: IraqiPlate(
                  number: vehicle.plateNumber,
                  letter: vehicle.plateLetter,
                  governorate: vehicle.plateGovernorate,
                  type: PlateType.fromCode(vehicle.plateType),
                  style: PlateStyle.fromCode(vehicle.plateStyle),
                  width: 168,
                ),
              ),
              const Spacer(),
              VehicleThumb(
                kind: thumbKindFor(
                    vehicleType: vehicle.vehicleType,
                    plateType: vehicle.plateType),
                color: Colors.white.withValues(alpha: 0.85),
                cutout: AppColors.brandBlack,
                width: 64,
              ),
            ],
          ),
          if (facts.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.md),
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: [
                for (final fact in facts)
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.10),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(fact,
                        style: const TextStyle(
                            color: AppColors.brandWhite, fontSize: 11)),
                  ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

/// The owner's plan no longer covers this vehicle (free: one, Plus: three).
class _LockedByPlanBanner extends StatelessWidget {
  const _LockedByPlanBanner();

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.statusDanger.withValues(alpha: 0.10),
        borderRadius: AppRadius.rMd,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Icon(Icons.lock_outline_rounded, color: AppColors.statusDanger),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: Text(l10n.vehicleLockedBanner,
                    style: TextStyle(
                        color: AppColors.ink900,
                        fontSize: 13.5,
                        height: 1.5,
                        fontWeight: FontWeight.w600)),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          AppButton(
            label: l10n.vehicleLockedAction,
            icon: Icons.workspace_premium_outlined,
            style: AppButtonStyle.accent,
            onPressed: () => context.push('/subscription'),
          ),
        ],
      ),
    );
  }
}

class _DisabledBanner extends StatelessWidget {
  const _DisabledBanner();

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.statusWarning.withValues(alpha: 0.14),
        borderRadius: AppRadius.rMd,
      ),
      child: Row(
        children: [
          Icon(Icons.pause_circle_outline_rounded,
              color: AppColors.statusWarning),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Text(l10n.vehicleDisabledBanner,
                style: TextStyle(
                    color: AppColors.ink900,
                    fontSize: 13.5,
                    fontWeight: FontWeight.w600)),
          ),
        ],
      ),
    );
  }
}

/// The two vehicle-level actions, side by side: pause/resume (reversible) and
/// delete (destructive, visually distinct). Each asks for confirmation and
/// states what it will do first. The server still enforces ownership.
class _VehicleActions extends ConsumerStatefulWidget {
  const _VehicleActions({
    required this.vehicle,
    required this.vehicleId,
    this.editOnly = false,
  });

  final VehicleModel vehicle;
  final String vehicleId;

  /// No code is linked yet: only editing is offered.
  final bool editOnly;

  @override
  ConsumerState<_VehicleActions> createState() => _VehicleActionsState();
}

class _VehicleActionsState extends ConsumerState<_VehicleActions> {
  bool _busy = false;

  bool get _disabled => widget.vehicle.status == 'inactive';

  Future<bool> _confirm({
    required String title,
    required String body,
    required String confirmLabel,
    required bool destructive,
  }) async {
    final l10n = AppLocalizations.of(context)!;
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        icon: Icon(
            destructive
                ? Icons.warning_amber_rounded
                : Icons.pause_circle_outline_rounded,
            color: destructive ? AppColors.statusDanger : AppColors.ink700,
            size: 32),
        title: Text(title, textAlign: TextAlign.center),
        content: Text(body, style: const TextStyle(height: 1.5)),
        actionsAlignment: MainAxisAlignment.spaceBetween,
        actions: [
          TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: Text(l10n.cancel)),
          TextButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: Text(confirmLabel,
                style: TextStyle(
                    color: destructive ? AppColors.statusDanger : null,
                    fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
    return result == true;
  }

  Future<void> _toggle() async {
    final l10n = AppLocalizations.of(context)!;
    final enable = _disabled;
    final ok = await _confirm(
      title: enable ? l10n.enableVehicleTitle : l10n.disableVehicleTitle,
      body: enable ? l10n.enableVehicleBody : l10n.disableVehicleBody,
      confirmLabel: enable ? l10n.enableConfirm : l10n.disableConfirm,
      destructive: false,
    );
    if (!ok || !mounted) return;

    setState(() => _busy = true);
    final messenger = ScaffoldMessenger.of(context);
    try {
      await ref
          .read(vehicleRepositoryProvider)
          .setStatus(widget.vehicleId, active: enable);
      ref.invalidate(vehicleDetailProvider(widget.vehicleId));
      await ref.read(vehicleListControllerProvider.notifier).refresh();
      messenger.showSnackBar(SnackBar(
          content: Text(
              enable ? l10n.vehicleEnabledDone : l10n.vehicleDisabledDone)));
    } catch (e) {
      messenger
          .showSnackBar(SnackBar(content: Text(asApiException(e).message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _delete() async {
    final l10n = AppLocalizations.of(context)!;
    final ok = await _confirm(
      title: l10n.deleteVehicleTitle,
      body: l10n.deleteVehicleBody,
      confirmLabel: l10n.delete,
      destructive: true,
    );
    if (!ok || !mounted) return;

    setState(() => _busy = true);
    final messenger = ScaffoldMessenger.of(context);
    final navigator = Navigator.of(context);
    try {
      await ref
          .read(vehicleListControllerProvider.notifier)
          .delete(widget.vehicleId);
      navigator.maybePop();
    } catch (e) {
      messenger
          .showSnackBar(SnackBar(content: Text(asApiException(e).message)));
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        AppButton(
          label: l10n.editVehicleAction,
          icon: Icons.edit_outlined,
          style: AppButtonStyle.secondary,
          onPressed: _busy
              ? null
              : () => context.push('/vehicles/${widget.vehicleId}/edit',
                  extra: widget.vehicle),
        ),
        const SizedBox(height: AppSpacing.md),
        Row(
          children: [
            // Pausing only means something once a code is linked.
            if (!widget.editOnly) ...[
              Expanded(
                child: AppButton(
                  label: _disabled ? l10n.enableVehicle : l10n.disableVehicle,
                  icon: _disabled
                      ? Icons.play_circle_outline_rounded
                      : Icons.pause_circle_outline_rounded,
                  style: AppButtonStyle.secondary,
                  onPressed: _busy ? null : _toggle,
                ),
              ),
              const SizedBox(width: AppSpacing.md),
            ],
            Expanded(
              child: AppButton(
                label: l10n.delete,
                icon: Icons.delete_outline_rounded,
                style: AppButtonStyle.danger,
                onPressed: _busy ? null : _delete,
              ),
            ),
          ],
        ),
      ],
    );
  }
}
