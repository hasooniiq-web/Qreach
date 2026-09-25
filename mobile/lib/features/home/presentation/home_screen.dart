import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/plate_badge.dart';
import '../../../shared/widgets/skeleton.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../auth/presentation/profile_menu_button.dart';
import '../../subscription/data/subscription_models.dart';
import '../../subscription/presentation/subscription_controller.dart';
import '../../vehicles/data/vehicle_model.dart';
import '../../vehicles/presentation/vehicle_controller.dart';

/// Answers, in order: who am I / which plan, is my vehicle protected, what
/// can I do next. Protection status is derived from the vehicle's real tags
/// (protected iff a tag is assigned) — never fabricated. There is no
/// recent-activity feed because no customer activity API exists yet.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final authState = ref.watch(authControllerProvider);
    final vehiclesAsync = ref.watch(vehicleListControllerProvider);
    final subscription = ref.watch(subscriptionProvider).valueOrNull;

    final name = authState is AuthAuthenticated ? authState.user.name : null;
    final greeting = (name != null && name.isNotEmpty)
        ? l10n.homeGreetingWithName(name)
        : l10n.homeGreetingNoName;

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(subscriptionProvider);
            await ref.read(vehicleListControllerProvider.notifier).refresh();
          },
          child: ListView(
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: [
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          greeting,
                          style: Theme.of(context)
                              .textTheme
                              .headlineSmall
                              ?.copyWith(fontWeight: FontWeight.w800),
                        ),
                        if (subscription != null) ...[
                          const SizedBox(height: AppSpacing.xs),
                          _PlanChip(plan: subscription.plan),
                        ],
                      ],
                    ),
                  ),
                  const ProfileMenuButton(),
                ],
              ),
              const SizedBox(height: AppSpacing.xl),
              vehiclesAsync.when(
                loading: () => const SkeletonBox(height: 176, radius: 16),
                error: (error, _) => ErrorState(
                  error: error,
                  onRetry: () => ref
                      .read(vehicleListControllerProvider.notifier)
                      .refresh(),
                ),
                data: (vehicles) => vehicles.isEmpty
                    ? _NoVehicleYetCard(l10n: l10n)
                    : _HeroVehicleCard(vehicle: vehicles.first, l10n: l10n),
              ),
              if (subscription != null &&
                  !subscription.plan.showsPublicContacts) ...[
                const SizedBox(height: AppSpacing.lg),
                _UpgradePrompt(l10n: l10n),
              ],
              const SizedBox(height: AppSpacing.xl),
              Text(
                l10n.quickActions,
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: AppSpacing.md),
              Row(
                children: [
                  _QuickAction(
                    icon: Icons.directions_car_outlined,
                    label: l10n.myVehicles,
                    onTap: () => context.push('/vehicles'),
                  ),
                  _QuickAction(
                    icon: Icons.add,
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

class _PlanChip extends StatelessWidget {
  const _PlanChip({required this.plan});

  final PlanModel plan;

  @override
  Widget build(BuildContext context) {
    final pro = plan.showsPublicContacts;

    return InkWell(
      borderRadius: BorderRadius.circular(999),
      onTap: () => context.push('/subscription'),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: pro ? AppColors.brandYellow : AppColors.ink100,
          borderRadius: BorderRadius.circular(999),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(pro ? Icons.workspace_premium : Icons.person_outline,
                size: 14, color: AppColors.brandBlack),
            const SizedBox(width: 4),
            Text(plan.name,
                style: const TextStyle(
                    color: AppColors.brandBlack,
                    fontSize: 12,
                    fontWeight: FontWeight.w700)),
          ],
        ),
      ),
    );
  }
}

class _HeroVehicleCard extends StatelessWidget {
  const _HeroVehicleCard({required this.vehicle, required this.l10n});

  final VehicleModel vehicle;
  final AppLocalizations l10n;

  @override
  Widget build(BuildContext context) {
    final assigned = vehicle.tags.where((t) => t.status == 'assigned').length;
    final isProtected = assigned > 0;

    return Material(
      color: AppColors.brandBlack,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => context.push('/vehicles/${vehicle.id}'),
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.xl),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(
                    isProtected ? Icons.verified_user : Icons.shield_outlined,
                    color: isProtected ? AppColors.brandYellow : Colors.white54,
                    size: 20,
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  Text(
                    isProtected
                        ? l10n.vehicleProtected
                        : l10n.vehicleNotProtectedYet,
                    style: TextStyle(
                        color: isProtected
                            ? AppColors.brandYellow
                            : Colors.white70,
                        fontWeight: FontWeight.w700),
                  ),
                  const Spacer(),
                  const Icon(Icons.chevron_left, color: Colors.white54),
                ],
              ),
              const SizedBox(height: AppSpacing.lg),
              Text(vehicle.displayName,
                  style: const TextStyle(
                      color: AppColors.brandWhite,
                      fontSize: 24,
                      fontWeight: FontWeight.w800)),
              if (vehicle.make != null || vehicle.model != null)
                Padding(
                  padding: const EdgeInsets.only(top: 2),
                  child: Text(
                    [vehicle.make, vehicle.model].whereType<String>().join(' '),
                    style: const TextStyle(color: Colors.white70),
                  ),
                ),
              const SizedBox(height: AppSpacing.lg),
              Row(
                children: [
                  PlateBadge(plate: vehicle.plateNumber, onDark: true),
                  const Spacer(),
                  Text(l10n.tagsLinkedCount(assigned),
                      style: const TextStyle(color: Colors.white70)),
                ],
              ),
            ],
          ),
        ),
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
      padding: const EdgeInsets.all(AppSpacing.xl),
      child: Row(
        children: [
          const CircleAvatar(
            radius: 24,
            backgroundColor: AppColors.brandYellow,
            child: Icon(Icons.add, color: AppColors.brandBlack),
          ),
          const SizedBox(width: AppSpacing.lg),
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

class _UpgradePrompt extends StatelessWidget {
  const _UpgradePrompt({required this.l10n});

  final AppLocalizations l10n;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.brandYellow.withValues(alpha: 0.16),
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => context.push('/subscription'),
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Row(
            children: [
              const Icon(Icons.workspace_premium_outlined),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(l10n.homeUpgradeTitle,
                        style: const TextStyle(fontWeight: FontWeight.w700)),
                    Text(l10n.homeUpgradeBody,
                        style: const TextStyle(
                            color: AppColors.ink600, fontSize: 13)),
                  ],
                ),
              ),
              const Icon(Icons.chevron_left, color: AppColors.ink500),
            ],
          ),
        ),
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
    return Expanded(
      child: InkResponse(
        onTap: onTap,
        radius: 44,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: AppSpacing.xs),
          child: Column(
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: AppColors.brandWhite,
                  shape: BoxShape.circle,
                  border: Border.all(color: AppColors.ink200),
                ),
                child: Icon(icon, color: AppColors.brandBlack),
              ),
              const SizedBox(height: AppSpacing.sm),
              Text(label,
                  textAlign: TextAlign.center,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                      fontSize: 12, fontWeight: FontWeight.w600)),
            ],
          ),
        ),
      ),
    );
  }
}
