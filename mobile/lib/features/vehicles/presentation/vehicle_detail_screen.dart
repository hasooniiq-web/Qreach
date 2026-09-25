import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../tags/data/tag_model.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/plate_badge.dart';
import '../../../shared/widgets/skeleton.dart';
import '../../../shared/widgets/status_badge.dart';
import '../data/vehicle_model.dart';
import 'vehicle_controller.dart';
import 'vehicle_type_labels.dart';

class VehicleDetailScreen extends ConsumerWidget {
  const VehicleDetailScreen({super.key, required this.vehicleId});

  final String vehicleId;

  Future<void> _confirmDelete(BuildContext context, WidgetRef ref) async {
    final l10n = AppLocalizations.of(context)!;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        content: Text(l10n.deleteVehicleConfirm),
        actions: [
          TextButton(
              onPressed: () => context.pop(false), child: Text(l10n.cancel)),
          TextButton(
            onPressed: () => context.pop(true),
            child: Text(l10n.delete,
                style: const TextStyle(color: AppColors.statusDanger)),
          ),
        ],
      ),
    );
    if (confirmed == true) {
      await ref.read(vehicleListControllerProvider.notifier).delete(vehicleId);
      if (context.mounted) context.pop();
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final vehicleAsync = ref.watch(vehicleDetailProvider(vehicleId));

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.vehicleDetails),
        actions: [
          PopupMenuButton<String>(
            onSelected: (value) {
              if (value == 'delete') _confirmDelete(context, ref);
            },
            itemBuilder: (context) => [
              PopupMenuItem(value: 'delete', child: Text(l10n.delete)),
            ],
          ),
        ],
      ),
      body: vehicleAsync.when(
        loading: () => const SkeletonList(count: 3),
        error: (error, _) => ErrorState(
          error: error,
          onRetry: () => ref.invalidate(vehicleDetailProvider(vehicleId)),
        ),
        data: (vehicle) => RefreshIndicator(
          onRefresh: () => ref.refresh(vehicleDetailProvider(vehicleId).future),
          child: ListView(
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: [
              _VehicleHero(vehicle: vehicle),
              const SizedBox(height: AppSpacing.xl),
              Text(l10n.vehicleCodeTitle,
                  style: Theme.of(context)
                      .textTheme
                      .titleMedium
                      ?.copyWith(fontWeight: FontWeight.w700)),
              const SizedBox(height: AppSpacing.md),
              _CodeSection(vehicle: vehicle, vehicleId: vehicleId),
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
                style: const TextStyle(color: AppColors.statusDanger)),
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

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (context) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(
              AppSpacing.xl, 0, AppSpacing.xl, AppSpacing.xl),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                  '${tag.type == 'nfc' ? l10n.tagTypeNfc : l10n.tagTypeQr}  ·  ${tag.serialNumber}',
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
                  errorCorrectionLevel: QrErrorCorrectLevel.H,
                ),
              ),
              const SizedBox(height: AppSpacing.lg),
              Text(l10n.codeLinkLabel,
                  style:
                      const TextStyle(color: AppColors.ink500, fontSize: 12)),
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
                const Icon(Icons.qr_code_2, size: 40, color: AppColors.ink300),
                const SizedBox(width: AppSpacing.lg),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(l10n.noCodeYetTitle,
                          style: const TextStyle(fontWeight: FontWeight.w700)),
                      Text(l10n.noCodeYetSubtitle,
                          style: const TextStyle(color: AppColors.ink500)),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          ElevatedButton.icon(
            onPressed: () => context.push('/vehicles/${widget.vehicleId}/scan'),
            icon: const Icon(Icons.qr_code_scanner),
            label: Text(l10n.linkNewTag),
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
              Icon(tag.type == 'nfc' ? Icons.nfc : Icons.qr_code_2,
                  size: 32, color: AppColors.ink800),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(tag.serialNumber,
                        style: const TextStyle(fontWeight: FontWeight.w800),
                        textDirection: TextDirection.ltr),
                    Text(tag.type == 'nfc' ? l10n.tagTypeNfc : l10n.tagTypeQr,
                        style: const TextStyle(
                            color: AppColors.ink500, fontSize: 12)),
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
        OutlinedButton.icon(
          onPressed: () => _showQr(context, tag),
          icon: const Icon(Icons.qr_code_2),
          label: Text(l10n.showQr),
        ),
        const SizedBox(height: AppSpacing.md),
        Text(l10n.oneCodeNote,
            style: const TextStyle(color: AppColors.ink500, fontSize: 13)),
        const SizedBox(height: AppSpacing.lg),
        OutlinedButton.icon(
          onPressed: _removing ? null : _remove,
          style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.statusDanger,
              side: const BorderSide(color: AppColors.statusDanger)),
          icon: _removing
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2))
              : const Icon(Icons.delete_outline),
          label: Text(l10n.removeCode),
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
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(isProtected ? Icons.verified_user : Icons.shield_outlined,
                  size: 20,
                  color: isProtected ? AppColors.brandYellow : Colors.white54),
              const SizedBox(width: AppSpacing.sm),
              Text(
                isProtected
                    ? l10n.vehicleProtected
                    : l10n.vehicleNotProtectedYet,
                style: TextStyle(
                    color: isProtected ? AppColors.brandYellow : Colors.white70,
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
          PlateBadge(plate: vehicle.plateNumber, onDark: true),
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
