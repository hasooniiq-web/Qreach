import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../../core/theme/app_colors.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/error_banner.dart';
import '../../../shared/widgets/status_badge.dart';
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
        actions: [
          PopupMenuButton<String>(
            onSelected: (value) async {
              if (value == 'delete') {
                final confirmed = await showDialog<bool>(
                  context: context,
                  builder: (context) => AlertDialog(
                    content: Text(l10n.deleteVehicleConfirm),
                    actions: [
                      TextButton(
                          onPressed: () => context.pop(false),
                          child: Text(l10n.cancel)),
                      TextButton(
                        onPressed: () => context.pop(true),
                        child: Text(l10n.delete,
                            style:
                                const TextStyle(color: AppColors.statusDanger)),
                      ),
                    ],
                  ),
                );
                if (confirmed == true) {
                  await ref
                      .read(vehicleListControllerProvider.notifier)
                      .delete(vehicleId);
                  if (context.mounted) context.pop();
                }
              }
            },
            itemBuilder: (context) => [
              PopupMenuItem(value: 'delete', child: Text(l10n.delete)),
            ],
          ),
        ],
      ),
      body: vehicleAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Padding(
          padding: const EdgeInsets.all(16),
          child: ErrorBanner(message: asApiException(error).message),
        ),
        data: (vehicle) {
          return RefreshIndicator(
            onRefresh: () =>
                ref.refresh(vehicleDetailProvider(vehicleId).future),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text(vehicle.displayName,
                    style: Theme.of(context)
                        .textTheme
                        .headlineSmall
                        ?.copyWith(fontWeight: FontWeight.w700)),
                const SizedBox(height: 4),
                Text(
                  '${vehicle.make ?? ''} ${vehicle.model ?? ''} · ${vehicleTypeLabel(context, vehicle.vehicleType)}'
                      .trim(),
                  style: const TextStyle(color: AppColors.ink500),
                ),
                const SizedBox(height: 8),
                Text(vehicle.plateNumber,
                    style: const TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 24),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(l10n.linkedTags,
                        style: Theme.of(context)
                            .textTheme
                            .titleMedium
                            ?.copyWith(fontWeight: FontWeight.w600)),
                    TextButton.icon(
                      onPressed: () =>
                          context.push('/vehicles/$vehicleId/scan'),
                      icon: const Icon(Icons.qr_code, size: 18),
                      label: Text(l10n.linkNewTag),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                if (vehicle.tags.isEmpty)
                  Text(l10n.noTagsYet,
                      style: const TextStyle(color: AppColors.ink400))
                else
                  ...vehicle.tags.map(
                    (tag) => Card(
                      margin: const EdgeInsets.only(bottom: 8),
                      child: ListTile(
                        leading: Icon(
                          tag.type == 'nfc' ? Icons.nfc : Icons.qr_code,
                          color: AppColors.ink600,
                        ),
                        title: Text(tag.serialNumber,
                            style:
                                const TextStyle(fontWeight: FontWeight.w600)),
                        trailing: StatusBadge(
                          label: tagStatusLabel(context, tag.status),
                          tone: tag.status == 'assigned'
                              ? StatusTone.success
                              : StatusTone.neutral,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}
