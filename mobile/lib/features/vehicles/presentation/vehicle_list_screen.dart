import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/plate_badge.dart';
import '../../../shared/widgets/skeleton.dart';
import '../../../shared/widgets/status_badge.dart';
import '../../auth/presentation/profile_menu_button.dart';
import 'vehicle_controller.dart';

class VehicleListScreen extends ConsumerWidget {
  const VehicleListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final vehiclesAsync = ref.watch(vehicleListControllerProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.myVehicles),
        actions: const [ProfileMenuButton()],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/vehicles/new'),
        backgroundColor: AppColors.brandYellow,
        foregroundColor: AppColors.brandBlack,
        icon: const Icon(Icons.add),
        label: Text(l10n.addVehicle),
      ),
      body: RefreshIndicator(
        onRefresh: () =>
            ref.read(vehicleListControllerProvider.notifier).refresh(),
        child: vehiclesAsync.when(
          loading: () => const SkeletonList(count: 4),
          error: (error, _) => ErrorState(
            error: error,
            onRetry: () =>
                ref.read(vehicleListControllerProvider.notifier).refresh(),
          ),
          data: (vehicles) {
            if (vehicles.isEmpty) {
              return ListView(
                children: [
                  SizedBox(
                    height: MediaQuery.of(context).size.height * 0.7,
                    child: EmptyState(
                      icon: Icons.directions_car_outlined,
                      title: l10n.noVehiclesTitle,
                      subtitle: l10n.noVehiclesSubtitle,
                    ),
                  ),
                ],
              );
            }

            return ListView.separated(
              // Bottom padding keeps the last card clear of the FAB.
              padding: const EdgeInsets.fromLTRB(
                  AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, 96),
              itemCount: vehicles.length,
              separatorBuilder: (_, __) =>
                  const SizedBox(height: AppSpacing.md),
              itemBuilder: (context, index) {
                final vehicle = vehicles[index];
                final isProtected =
                    vehicle.tags.any((tag) => tag.status == 'assigned');

                return AppCard(
                  onTap: () => context.push('/vehicles/${vehicle.id}'),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(vehicle.displayName,
                                style: const TextStyle(
                                    fontWeight: FontWeight.w700, fontSize: 16)),
                          ),
                          StatusBadge(
                            label: isProtected
                                ? l10n.vehicleProtected
                                : l10n.vehicleNotProtectedYet,
                            tone: isProtected
                                ? StatusTone.success
                                : StatusTone.warning,
                          ),
                        ],
                      ),
                      if (vehicle.make != null || vehicle.model != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 2),
                          child: Text(
                            [vehicle.make, vehicle.model]
                                .whereType<String>()
                                .join(' '),
                            style: const TextStyle(color: AppColors.ink500),
                          ),
                        ),
                      const SizedBox(height: AppSpacing.md),
                      PlateBadge(plate: vehicle.plateNumber),
                    ],
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }
}
