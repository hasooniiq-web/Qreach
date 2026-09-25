import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../../core/theme/app_colors.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/error_banner.dart';
import '../../auth/presentation/logout_confirm.dart';
import 'vehicle_controller.dart';

enum _ProfileMenuAction { profile, logout }

class VehicleListScreen extends ConsumerWidget {
  const VehicleListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final vehiclesAsync = ref.watch(vehicleListControllerProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.myVehicles),
        actions: [
          PopupMenuButton<_ProfileMenuAction>(
            icon: const CircleAvatar(
              radius: 16,
              backgroundColor: AppColors.ink100,
              child: Icon(Icons.person_outline,
                  size: 18, color: AppColors.ink600),
            ),
            onSelected: (action) {
              switch (action) {
                case _ProfileMenuAction.profile:
                  context.push('/profile');
                case _ProfileMenuAction.logout:
                  confirmAndLogout(context, ref, allDevices: false);
              }
            },
            itemBuilder: (context) => [
              PopupMenuItem(
                value: _ProfileMenuAction.profile,
                child: ListTile(
                  leading: const Icon(Icons.account_circle_outlined),
                  title: Text(l10n.profile),
                  contentPadding: EdgeInsets.zero,
                ),
              ),
              PopupMenuItem(
                value: _ProfileMenuAction.logout,
                child: ListTile(
                  leading: const Icon(Icons.logout,
                      color: AppColors.statusDanger),
                  title: Text(l10n.logout,
                      style: const TextStyle(color: AppColors.statusDanger)),
                  contentPadding: EdgeInsets.zero,
                ),
              ),
            ],
          ),
        ],
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
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => ListView(
            padding: const EdgeInsets.all(16),
            children: [ErrorBanner(message: asApiException(error).message)],
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
              padding: const EdgeInsets.all(16),
              itemCount: vehicles.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final vehicle = vehicles[index];
                return Card(
                  child: ListTile(
                    contentPadding:
                        const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    leading: const CircleAvatar(
                      backgroundColor: AppColors.ink100,
                      child: Icon(Icons.directions_car_outlined,
                          color: AppColors.ink600),
                    ),
                    title: Text(vehicle.displayName,
                        style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Text(
                      [
                        if (vehicle.make != null) vehicle.make,
                        if (vehicle.model != null) vehicle.model,
                      ].whereType<String>().join(' '),
                    ),
                    trailing: vehicle.tags.isNotEmpty
                        ? const Icon(Icons.qr_code,
                            size: 18, color: AppColors.statusSuccess)
                        : const Icon(Icons.chevron_left,
                            size: 18, color: AppColors.ink300),
                    onTap: () => context.push('/vehicles/${vehicle.id}'),
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
