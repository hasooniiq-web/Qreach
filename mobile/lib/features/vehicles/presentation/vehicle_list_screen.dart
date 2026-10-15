import 'package:flutter/material.dart';
import 'vehicle_type_labels.dart';
import 'add_vehicle_flow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/design/motion.dart';
import '../../../core/design/tokens.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/iraqi_plate.dart';
import '../../../shared/widgets/screen_header.dart';
import '../../../shared/widgets/skeleton.dart';
import '../../../shared/widgets/status_badge.dart';
import '../../../shared/widgets/vehicle_thumb.dart';
import '../data/vehicle_model.dart';
import 'vehicle_controller.dart';

/// All of the owner's vehicles. Each row leads with the vehicle's picture and
/// its status; the plate is drawn as the real plate. Rows enter one after the
/// other, and the add action lives in the header where it is always reachable.
class VehicleListScreen extends ConsumerWidget {
  const VehicleListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final vehiclesAsync = ref.watch(vehicleListControllerProvider);
    final count = vehiclesAsync.valueOrNull?.length;

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            ScreenHeader(
              title: l10n.myVehicles,
              trailing: HeaderIconButton(
                icon: Icons.add_rounded,
                tooltip: l10n.addVehicle,
                filled: true,
                onTap: () => startAddVehicle(context, ref),
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                color: AppColors.onSurface,
                backgroundColor: AppColors.surface,
                onRefresh: () =>
                    ref.read(vehicleListControllerProvider.notifier).refresh(),
                child: SwapFade(
                  child: vehiclesAsync.when(
                    loading: () => const _ListSkeleton(),
                    error: (error, _) => ErrorState(
                      key: const ValueKey('error'),
                      error: error,
                      onRetry: () => ref
                          .read(vehicleListControllerProvider.notifier)
                          .refresh(),
                    ),
                    data: (vehicles) {
                      if (vehicles.isEmpty) {
                        return ListView(
                          key: const ValueKey('empty'),
                          children: [
                            SizedBox(
                              height: MediaQuery.of(context).size.height * 0.6,
                              child: EmptyState(
                                icon: Icons.directions_car_rounded,
                                title: l10n.noVehiclesTitle,
                                subtitle: l10n.noVehiclesSubtitle,
                                action: AppButton(
                                  label: l10n.addVehicle,
                                  icon: Icons.add_rounded,
                                  expand: false,
                                  style: AppButtonStyle.accent,
                                  onPressed: () =>
                                      startAddVehicle(context, ref),
                                ),
                              ),
                            ),
                          ],
                        );
                      }

                      return ListView.separated(
                        key: ValueKey('list-${count ?? 0}'),
                        // Room under the floating navigation bar.
                        padding: const EdgeInsets.fromLTRB(
                            AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, 120),
                        itemCount: vehicles.length,
                        separatorBuilder: (_, __) =>
                            const SizedBox(height: AppSpacing.md),
                        itemBuilder: (context, index) => FadeSlideIn(
                          index: index,
                          child: _VehicleRow(vehicle: vehicles[index]),
                        ),
                      );
                    },
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ListSkeleton extends StatelessWidget {
  const _ListSkeleton();

  @override
  Widget build(BuildContext context) {
    return ListView(
      key: const ValueKey('loading'),
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: const [
        SkeletonBox(height: 150, radius: AppRadius.md),
        SizedBox(height: AppSpacing.md),
        SkeletonBox(height: 150, radius: AppRadius.md),
        SizedBox(height: AppSpacing.md),
        SkeletonBox(height: 150, radius: AppRadius.md),
      ],
    );
  }
}

class _VehicleRow extends StatelessWidget {
  const _VehicleRow({required this.vehicle});

  final VehicleModel vehicle;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final isProtected = vehicle.tags.any((tag) => tag.status == 'assigned');
    final subtitle =
        [vehicle.make, vehicle.model].whereType<String>().join(' ');

    return Pressable(
      scale: 0.985,
      onTap: () => context.push('/vehicles/${vehicle.id}'),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: AppRadius.rMd,
          boxShadow: AppShadows.card,
          border: AppShadows.hairline,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                          vehicle.nameOrNull ??
                              (subtitle.isNotEmpty
                                  ? subtitle
                                  : vehicleTypeLabel(context, vehicle.vehicleType)),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style:
                              AppText.section(context).copyWith(fontSize: 17)),
                      if (subtitle.isNotEmpty && vehicle.nameOrNull != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 2),
                          child: Text(subtitle,
                              style: AppText.body(context)
                                  .copyWith(color: AppColors.ink500)),
                        ),
                      const SizedBox(height: AppSpacing.sm),
                      StatusBadge(
                        label: vehicle.lockedByPlan
                            ? l10n.vehicleLockedBadge
                            : (isProtected
                                ? l10n.vehicleProtected
                                : l10n.vehicleNotProtectedYet),
                        tone: vehicle.lockedByPlan
                            ? StatusTone.danger
                            : (isProtected
                                ? StatusTone.success
                                : StatusTone.warning),
                      ),
                    ],
                  ),
                ),
                VehicleThumb(
                  kind: thumbKindFor(
                      vehicleType: vehicle.vehicleType,
                      plateType: vehicle.plateType),
                  color: AppColors.ink700,
                  cutout: AppColors.surface,
                  width: 92,
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            Row(
              children: [
                Icon(Icons.chevron_right_rounded, color: AppColors.ink300),
                const Spacer(),
                IraqiPlate(
                  number: vehicle.plateNumber,
                  letter: vehicle.plateLetter,
                  governorate: vehicle.plateGovernorate,
                  type: PlateType.fromCode(vehicle.plateType),
                  style: PlateStyle.fromCode(vehicle.plateStyle),
                  width: 176,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
