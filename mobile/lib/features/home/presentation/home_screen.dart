import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/iraqi_plate.dart';
import '../../../shared/widgets/notification_bell.dart';
import '../../../shared/widgets/plate_labels.dart';
import '../../../shared/widgets/skeleton.dart';
import '../../notifications/presentation/notification_tile.dart';
import '../../profile/presentation/profile_controller.dart';
import '../../vehicles/data/vehicle_model.dart';
import '../../vehicles/presentation/vehicle_controller.dart';
import '../../vehicles/presentation/vehicle_type_labels.dart';
import '../data/home_models.dart';
import 'home_controller.dart';

/// Home, top to bottom: the vehicles carousel, the latest three
/// conversations, then four equal activity areas. The bell in the header is
/// the only place notifications appear. Data refreshes on its own (see the
/// live poller in the app shell) — no pull-to-refresh needed.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final vehiclesAsync = ref.watch(vehicleListControllerProvider);
    final profile = ref.watch(profileProvider).valueOrNull;
    final home = ref.watch(homeProvider);

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(profileProvider);
            ref.invalidate(homeProvider);
            await ref.read(vehicleListControllerProvider.notifier).refresh();
          },
          child: ListView(
            padding: const EdgeInsets.only(bottom: AppSpacing.xxl),
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(
                    AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, 0),
                child: Row(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: const BoxDecoration(
                          color: AppColors.brandYellow, shape: BoxShape.circle),
                      alignment: Alignment.center,
                      child: const Text('Q',
                          style: TextStyle(
                              color: AppColors.brandBlack,
                              fontSize: 20,
                              fontWeight: FontWeight.w800)),
                    ),
                    const SizedBox(width: AppSpacing.sm),
                    Expanded(
                      child: Text(
                        (profile?.name?.isNotEmpty == true)
                            ? l10n.homeGreetingWithName(profile!.name!)
                            : l10n.homeGreetingNoName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                            fontWeight: FontWeight.w800, fontSize: 18),
                      ),
                    ),
                    const NotificationBell(),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.lg),

              // 1. Vehicles carousel
              vehiclesAsync.when(
                loading: () => const Padding(
                  padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
                  child: SkeletonBox(height: 220, radius: 24),
                ),
                error: (error, _) => ErrorState(
                  error: error,
                  onRetry: () => ref
                      .read(vehicleListControllerProvider.notifier)
                      .refresh(),
                ),
                data: (list) => list.isEmpty
                    ? const Padding(
                        padding:
                            EdgeInsets.symmetric(horizontal: AppSpacing.lg),
                        child: _NoVehicleCard(),
                      )
                    : VehicleCarousel(vehicles: list, ownerName: profile?.name),
              ),
              const SizedBox(height: AppSpacing.xl),

              // 2. Latest conversations
              _SectionHeader(
                title: l10n.homeConversations,
                actionLabel: l10n.viewAll,
                onAction: () => context.go('/contacts'),
              ),
              home.when(
                loading: () => const Padding(
                  padding: EdgeInsets.all(AppSpacing.lg),
                  child: SkeletonBox(height: 120, radius: 16),
                ),
                error: (error, _) => ErrorState(
                    error: error, onRetry: () => ref.invalidate(homeProvider)),
                data: (data) => Column(
                  children: [
                    _Conversations(items: data.conversations),
                    const SizedBox(height: AppSpacing.xl),

                    // 3. Activities
                    _SectionHeader(title: l10n.homeActivities),
                    _ActivityGrid(data: data),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title, this.actionLabel, this.onAction});

  final String title;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(AppSpacing.lg, 0, AppSpacing.md, 4),
      child: Row(
        children: [
          Expanded(
            child: Text(title,
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.w800)),
          ),
          if (actionLabel != null)
            TextButton(onPressed: onAction, child: Text(actionLabel!)),
        ],
      ),
    );
  }
}

/// Swipeable vehicle cards with a slow autoplay. Autoplay pauses while the
/// user is touching the carousel, and does nothing for a single vehicle.
class VehicleCarousel extends StatefulWidget {
  const VehicleCarousel({
    super.key,
    required this.vehicles,
    required this.ownerName,
    this.autoplay = true,
  });

  final List<VehicleModel> vehicles;
  final String? ownerName;
  final bool autoplay;

  @override
  State<VehicleCarousel> createState() => _VehicleCarouselState();
}

class _VehicleCarouselState extends State<VehicleCarousel> {
  final _controller = PageController(viewportFraction: 0.9);
  Timer? _timer;
  int _page = 0;
  bool _touching = false;

  @override
  void initState() {
    super.initState();
    if (widget.autoplay) {
      _timer = Timer.periodic(const Duration(seconds: 5), (_) => _advance());
    }
  }

  void _advance() {
    if (_touching || !mounted || widget.vehicles.length < 2) return;
    final next = (_page + 1) % widget.vehicles.length;
    _controller.animateToPage(next,
        duration: const Duration(milliseconds: 650),
        curve: Curves.easeInOutCubic);
  }

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final count = widget.vehicles.length;

    return Column(
      children: [
        SizedBox(
          height: 224,
          child: Listener(
            onPointerDown: (_) => _touching = true,
            onPointerUp: (_) => _touching = false,
            onPointerCancel: (_) => _touching = false,
            child: PageView.builder(
              controller: _controller,
              itemCount: count,
              onPageChanged: (i) => setState(() => _page = i),
              itemBuilder: (context, i) => Padding(
                padding: const EdgeInsets.symmetric(horizontal: 6),
                child: _VehicleCard(
                    vehicle: widget.vehicles[i], ownerName: widget.ownerName),
              ),
            ),
          ),
        ),
        if (count > 1) ...[
          const SizedBox(height: AppSpacing.sm),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              for (var i = 0; i < count; i++)
                AnimatedContainer(
                  duration: const Duration(milliseconds: 250),
                  margin: const EdgeInsets.symmetric(horizontal: 3),
                  width: i == _page ? 20 : 6,
                  height: 6,
                  decoration: BoxDecoration(
                    color: i == _page ? AppColors.brandBlack : AppColors.ink200,
                    borderRadius: BorderRadius.circular(3),
                  ),
                ),
            ],
          ),
        ],
      ],
    );
  }
}

class _VehicleCard extends StatelessWidget {
  const _VehicleCard({required this.vehicle, required this.ownerName});

  final VehicleModel vehicle;
  final String? ownerName;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final linked = vehicle.tags.any((t) => t.status == 'assigned');
    final statusColor =
        linked ? const Color(0xFF43A047) : const Color(0xFFE53935);

    return Material(
      color: AppColors.brandBlack,
      borderRadius: BorderRadius.circular(24),
      child: InkWell(
        borderRadius: BorderRadius.circular(24),
        onTap: () => context.push('/vehicles/${vehicle.id}'),
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(linked ? Icons.verified_user : Icons.gpp_bad,
                      color: statusColor, size: 22),
                  const SizedBox(width: 6),
                  Text(linked ? l10n.homeLinked : l10n.homeNotLinked,
                      style: TextStyle(
                          color: statusColor,
                          fontWeight: FontWeight.w800,
                          fontSize: 14)),
                  const Spacer(),
                  Text(vehicleTypeLabel(context, vehicle.vehicleType),
                      style:
                          const TextStyle(color: Colors.white70, fontSize: 13)),
                ],
              ),
              const Spacer(),
              Center(
                child: IraqiPlate(
                  number: vehicle.plateNumber,
                  letter: vehicle.plateLetter,
                  governorate: vehicle.plateGovernorate,
                  type: PlateType.fromCode(vehicle.plateType),
                  width: 250,
                ),
              ),
              const SizedBox(height: 6),
              Center(
                child: Text(
                  plateTypeLabel(l10n, PlateType.fromCode(vehicle.plateType)),
                  style: const TextStyle(color: Colors.white54, fontSize: 11),
                ),
              ),
              const Spacer(),
              Row(
                children: [
                  const Icon(Icons.person_outline,
                      color: Colors.white54, size: 18),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      '${l10n.homeOwnerLabel}: ${ownerName?.isNotEmpty == true ? ownerName! : l10n.notSet}',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          color: AppColors.brandWhite, fontSize: 14),
                    ),
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

class _NoVehicleCard extends StatelessWidget {
  const _NoVehicleCard();

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Material(
      color: AppColors.brandBlack,
      borderRadius: BorderRadius.circular(24),
      child: InkWell(
        borderRadius: BorderRadius.circular(24),
        onTap: () => context.push('/vehicles/new'),
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.xl),
          child: Row(
            children: [
              const CircleAvatar(
                radius: 26,
                backgroundColor: AppColors.brandYellow,
                child: Icon(Icons.add, color: AppColors.brandBlack),
              ),
              const SizedBox(width: AppSpacing.lg),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(l10n.noVehicleYetHomeTitle,
                        style: const TextStyle(
                            color: AppColors.brandWhite,
                            fontWeight: FontWeight.w800,
                            fontSize: 16)),
                    const SizedBox(height: 2),
                    Text(l10n.noVehicleYetHomeSubtitle,
                        style: const TextStyle(color: Colors.white70)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Conversations extends StatelessWidget {
  const _Conversations({required this.items});

  final List<HomeConversation> items;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    if (items.isEmpty) {
      return Padding(
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.lg, vertical: AppSpacing.sm),
        child: Align(
          alignment: AlignmentDirectional.centerStart,
          child: Text(l10n.homeConversationsEmpty,
              style: const TextStyle(color: AppColors.ink500)),
        ),
      );
    }

    return Column(
      children: [
        for (final c in items)
          InkWell(
            onTap: () => context.push('/contacts/${c.id}'),
            child: Padding(
              padding: const EdgeInsets.symmetric(
                  horizontal: AppSpacing.lg, vertical: 10),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 20,
                    backgroundColor:
                        c.unread ? AppColors.brandYellow : AppColors.ink100,
                    child: Text(
                      (c.visitorName?.isNotEmpty == true
                              ? c.visitorName!
                              : l10n.visitorAnonymous)
                          .characters
                          .first,
                      style: const TextStyle(
                          color: AppColors.brandBlack,
                          fontWeight: FontWeight.w800),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                c.visitorName?.isNotEmpty == true
                                    ? c.visitorName!
                                    : l10n.visitorAnonymous,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                    fontWeight: c.unread
                                        ? FontWeight.w800
                                        : FontWeight.w600),
                              ),
                            ),
                            Text(notificationTime(c.at),
                                style: const TextStyle(
                                    color: AppColors.ink400, fontSize: 11)),
                          ],
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '${c.fromOwner ? l10n.youPrefix : ''}${c.preview}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                              color: c.unread
                                  ? AppColors.ink900
                                  : AppColors.ink500,
                              fontSize: 13),
                        ),
                      ],
                    ),
                  ),
                  if (c.unread)
                    Container(
                      margin: const EdgeInsetsDirectional.only(start: 8),
                      width: 9,
                      height: 9,
                      decoration: const BoxDecoration(
                          color: AppColors.brandBlack, shape: BoxShape.circle),
                    ),
                ],
              ),
            ),
          ),
      ],
    );
  }
}

/// Four equal areas in a 2x2 grid — same size whatever they contain.
class _ActivityGrid extends StatelessWidget {
  const _ActivityGrid({required this.data});

  final HomeData data;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final tiles = [
      _Tile(l10n.actLatest, Icons.bolt_outlined, data.latest, null),
      _Tile(l10n.actCommunication, Icons.forum_outlined, data.communication,
          '/contacts'),
      _Tile(l10n.actAccount, Icons.person_outline, data.account, '/profile'),
      _Tile(l10n.actReports, Icons.flag_outlined, data.reports, '/contacts'),
    ];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
      child: GridView.count(
        crossAxisCount: 2,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        mainAxisSpacing: AppSpacing.md,
        crossAxisSpacing: AppSpacing.md,
        childAspectRatio: 1.05,
        children: [for (final t in tiles) _ActivityTile(tile: t)],
      ),
    );
  }
}

class _Tile {
  const _Tile(this.title, this.icon, this.items, this.route);

  final String title;
  final IconData icon;
  final List<ActivityItem> items;
  final String? route;
}

class _ActivityTile extends StatelessWidget {
  const _ActivityTile({required this.tile});

  final _Tile tile;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Material(
      color: AppColors.brandWhite,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: tile.route == null ? null : () => context.go(tile.route!),
        child: Container(
          padding: const EdgeInsets.all(AppSpacing.md),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.ink100),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(tile.icon, size: 18, color: AppColors.ink700),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(tile.title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                            fontWeight: FontWeight.w800, fontSize: 13)),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              Expanded(
                child: tile.items.isEmpty
                    ? Align(
                        alignment: AlignmentDirectional.centerStart,
                        child: Text(l10n.actNone,
                            style: const TextStyle(
                                color: AppColors.ink400, fontSize: 12)),
                      )
                    : Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          for (final item in tile.items.take(3))
                            Padding(
                              padding: const EdgeInsets.only(bottom: 6),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(item.text,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(fontSize: 12)),
                                  Text(notificationTime(item.at),
                                      style: const TextStyle(
                                          color: AppColors.ink400,
                                          fontSize: 10)),
                                ],
                              ),
                            ),
                        ],
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
