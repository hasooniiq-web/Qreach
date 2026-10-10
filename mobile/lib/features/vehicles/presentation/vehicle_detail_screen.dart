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
              const SizedBox(height: AppSpacing.xxl),
              FadeSlideIn(
                index: 2,
                child: _VehicleActions(vehicle: vehicle, vehicleId: vehicleId),
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
              const SizedBox(height: AppSpacing.lg),
              Text(l10n.codeLinkLabel,
                  style: TextStyle(color: AppColors.ink500, fontSize: 12)),
              const SizedBox(height: 4),
              SelectableText(tag.publicUrl,
                  textDirection: TextDirection.ltr,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 12)),
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
          AppButton(
            label: l10n.linkNewTag,
            icon: Icons.qr_code_scanner_rounded,
            style: AppButtonStyle.accent,
            onPressed: () => context.push('/vehicles/${widget.vehicleId}/scan'),
          ),
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
        Text(l10n.oneCodeNote,
            style: TextStyle(color: AppColors.ink500, fontSize: 13)),
        const SizedBox(height: AppSpacing.lg),
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

    return Container(
      padding: const EdgeInsets.all(AppSpacing.xl),
      decoration: BoxDecoration(
        color: AppColors.brandBlack,
        borderRadius: AppRadius.rLg,
        boxShadow: AppShadows.raised,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(isProtected ? Icons.link_rounded : Icons.link_off_rounded,
                  size: 22,
                  color: isProtected
                      ? const Color(0xFF4CD264)
                      : const Color(0xFFFF6B5E)),
              const SizedBox(width: AppSpacing.sm),
              Text(
                isProtected
                    ? l10n.vehicleProtected
                    : l10n.vehicleNotProtectedYet,
                style: TextStyle(
                    color: isProtected
                        ? const Color(0xFF4CD264)
                        : const Color(0xFFFF6B5E),
                    fontWeight: FontWeight.w700),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.lg),
          Text(vehicle.displayName,
              style: const TextStyle(
                  color: AppColors.brandWhite,
                  fontSize: 26,
                  fontWeight: FontWeight.w800)),
          const SizedBox(height: AppSpacing.md),
          Row(
            children: [
              VehicleThumb(
                kind: thumbKindFor(
                    vehicleType: vehicle.vehicleType,
                    plateType: vehicle.plateType),
                color: Colors.white.withValues(alpha: 0.9),
                cutout: AppColors.brandBlack,
                width: 84,
              ),
              const Spacer(),
              Hero(
                tag: 'plate-${vehicle.id}',
                child: IraqiPlate(
                  number: vehicle.plateNumber,
                  letter: vehicle.plateLetter,
                  governorate: vehicle.plateGovernorate,
                  type: PlateType.fromCode(vehicle.plateType),
                  width: 190,
                ),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.lg),
          Wrap(
            spacing: AppSpacing.sm,
            runSpacing: AppSpacing.sm,
            children: [
              for (final fact in facts)
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(fact,
                      style: const TextStyle(
                          color: AppColors.brandWhite, fontSize: 12)),
                ),
            ],
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
  const _VehicleActions({required this.vehicle, required this.vehicleId});

  final VehicleModel vehicle;
  final String vehicleId;

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
    return Row(
      children: [
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
        Expanded(
          child: AppButton(
            label: l10n.delete,
            icon: Icons.delete_outline_rounded,
            style: AppButtonStyle.danger,
            onPressed: _busy ? null : _delete,
          ),
        ),
      ],
    );
  }
}
