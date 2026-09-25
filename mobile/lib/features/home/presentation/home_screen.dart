import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/error_banner.dart';
import '../../../shared/widgets/status_badge.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../auth/presentation/profile_menu_button.dart';
import '../../vehicles/data/vehicle_model.dart';
import '../../vehicles/presentation/vehicle_controller.dart';

/// The app's real home screen (see docs/UI_UX_REDESIGN_AUDIT.md — the
/// vehicle list previously doubled as "home" with no summary/status view of
/// its own). Shows the primary vehicle's real protection status (derived
/// from its already-fetched tags, not fabricated) plus quick actions to the
/// rest of the app.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final authState = ref.watch(authControllerProvider);
    final vehiclesAsync = ref.watch(vehicleListControllerProvider);

    final name = authState is AuthAuthenticated ? authState.user.name : null;
    final greeting = (name != null && name.isNotEmpty)
        ? l10n.homeGreetingWithName(name)
        : l10n.homeGreetingNoName;

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () =>
              ref.read(vehicleListControllerProvider.notifier).refresh(),
          child: ListView(
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      greeting,
                      style: Theme.of(context)
                          .textTheme
                          .headlineSmall
                          ?.copyWith(fontWeight: FontWeight.w700),
                    ),
                  ),
                  const ProfileMenuButton(),
                ],
              ),
              const SizedBox(height: AppSpacing.xl),
              vehiclesAsync.when(
                loading: () =>
                    const Center(child: CircularProgressIndicator()),
                error: (error, _) =>
                    ErrorBanner(message: asApiException(error).message),
                data: (vehicles) => vehicles.isEmpty
                    ? _NoVehicleYetCard(l10n: l10n)
                    : _PrimaryVehicleCard(vehicle: vehicles.first, l10n: l10n),
              ),
              const SizedBox(height: AppSpacing.xl),
              Text(
                l10n.quickActions,
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: AppSpacing.md),
              GridView.count(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisCount: 2,
                mainAxisSpacing: AppSpacing.md,
                crossAxisSpacing: AppSpacing.md,
                childAspectRatio: 1.5,
                children: [
                  _QuickAction(
                    icon: Icons.directions_car_outlined,
                    label: l10n.myVehicles,
                    onTap: () => context.push('/vehicles'),
                  ),
                  _QuickAction(
                    icon: Icons.add_circle_outline,
                    label: l10n.addVehicle,
                    onTap: () => context.push('/vehicles/new'),
                  ),
                  _QuickAction(
                    icon: Icons.notifications_outlined,
                    label: l10n.notifications,
                    onTap: () => context.push('/notifications'),
                  ),
                  _QuickAction(
                    icon: Icons.person_outline,
                    label: l10n.profile,
                    onTap: () => context.push('/profile'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _PrimaryVehicleCard extends StatelessWidget {
  const _PrimaryVehicleCard({required this.vehicle, required this.l10n});

  final VehicleModel vehicle;
  final AppLocalizations l10n;

  @override
  Widget build(BuildContext context) {
    final isProtected = vehicle.tags.any((tag) => tag.status == 'assigned');

    return AppCard(
      onTap: () => context.push('/vehicles/${vehicle.id}'),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              const CircleAvatar(
                radius: 24,
                backgroundColor: AppColors.ink100,
                child: Icon(Icons.directions_car_outlined,
                    color: AppColors.ink600),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(vehicle.displayName,
                        style:
                            const TextStyle(fontWeight: FontWeight.w700)),
                    if (vehicle.make != null || vehicle.model != null)
                      Text(
                        [vehicle.make, vehicle.model]
                            .whereType<String>()
                            .join(' '),
                        style: const TextStyle(color: AppColors.ink500),
                      ),
                  ],
                ),
              ),
              StatusBadge(
                label: isProtected
                    ? l10n.vehicleProtected
                    : l10n.vehicleNotProtectedYet,
                tone: isProtected ? StatusTone.success : StatusTone.warning,
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _NoVehicleYetCard extends StatelessWidget {
  const _NoVehicleYetCard({required this.l10n});

  final AppLocalizations l10n;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      onTap: () => context.push('/vehicles/new'),
      child: Row(
        children: [
          const CircleAvatar(
            radius: 24,
            backgroundColor: AppColors.ink100,
            child: Icon(Icons.add, color: AppColors.ink600),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(l10n.noVehicleYetHomeTitle,
                    style: const TextStyle(fontWeight: FontWeight.w700)),
                Text(l10n.noVehicleYetHomeSubtitle,
                    style: const TextStyle(color: AppColors.ink500)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      onTap: onTap,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: AppColors.brandBlack),
          const SizedBox(height: AppSpacing.sm),
          Text(label,
              textAlign: TextAlign.center,
              style: const TextStyle(fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
