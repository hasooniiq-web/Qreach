import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/design/motion.dart';
import '../../../core/design/tokens.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/iraqi_plate.dart';
import '../../../shared/widgets/notification_bell.dart';
import '../../../shared/widgets/plate_labels.dart';
import '../../../shared/widgets/skeleton.dart';
import '../../../shared/widgets/user_avatar.dart';
import '../../../shared/widgets/vehicle_thumb.dart';
import '../../notifications/presentation/notification_tile.dart';
import '../../profile/presentation/profile_controller.dart';
import '../../vehicles/data/vehicle_model.dart';
import '../../vehicles/presentation/vehicle_controller.dart';
import '../../vehicles/presentation/vehicle_type_labels.dart';
import '../data/home_models.dart';
import 'home_controller.dart';

/// Home, deliberately minimal: who you are (photo + greeting + the bell, the
/// only place notifications appear), your vehicles as a swipeable carousel,
/// and your three latest conversations. Data refreshes on its own (see the
/// live poller in the app shell) — no manual refresh needed, though pulling
/// down still works.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final vehiclesAsync = ref.watch(vehicleListControllerProvider);
    final profile = ref.watch(profileProvider).valueOrNull;
    final home = ref.watch(homeProvider);
    final unread = home.valueOrNull?.unreadMessages ?? 0;

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: RefreshIndicator(
          color: AppColors.onSurface,
          backgroundColor: AppColors.surface,
          onRefresh: () async {
            ref.invalidate(profileProvider);
            ref.invalidate(homeProvider);
            await ref.read(vehicleListControllerProvider.notifier).refresh();
          },
          child: ListView(
            // Room under the floating navigation bar.
            padding: const EdgeInsets.only(bottom: 120),
            children: [
              FadeSlideIn(child: _Header(name: profile?.name)),
              const SizedBox(height: AppSpacing.xl),

              // Vehicles
              FadeSlideIn(
                index: 1,
                child: SwapFade(
                  child: vehiclesAsync.when(
                    loading: () => const Padding(
                      key: ValueKey('vehicles-loading'),
                      padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
                      child: SkeletonBox(height: 236, radius: AppRadius.lg),
                    ),
                    error: (error, _) => SizedBox(
                      key: const ValueKey('vehicles-error'),
                      height: 236,
                      child: ErrorState(
                        error: error,
                        onRetry: () => ref
                            .read(vehicleListControllerProvider.notifier)
                            .refresh(),
                      ),
                    ),
                    data: (list) => list.isEmpty
                        ? const Padding(
                            key: ValueKey('vehicles-empty'),
                            padding:
                                EdgeInsets.symmetric(horizontal: AppSpacing.lg),
                            child: _NoVehicleCard(),
                          )
                        : VehicleCarousel(
                            key: const ValueKey('vehicles-data'),
                            vehicles: list),
                  ),
                ),
              ),
              const SizedBox(height: AppSpacing.xl),

              // Conversations
              FadeSlideIn(
                index: 2,
                child: _SectionHeader(
                  title: l10n.homeConversations,
                  unread: unread,
                  actionLabel: l10n.viewAll,
                  onAction: () => context.go('/contacts'),
                ),
              ),
              SwapFade(
                child: home.when(
                  loading: () => const Padding(
                    key: ValueKey('conv-loading'),
                    padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
                    child: Column(children: [
                      SkeletonRow(),
                      SkeletonRow(),
                      SkeletonRow()
                    ]),
                  ),
                  error: (error, _) => ErrorState(
                    key: const ValueKey('conv-error'),
                    error: error,
                    onRetry: () => ref.invalidate(homeProvider),
                  ),
                  data: (data) => _Conversations(
                    key: const ValueKey('conv-data'),
                    items: data.conversations,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.name});

  final String? name;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final hasName = name?.isNotEmpty == true;

    return Padding(
      padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg, AppSpacing.md, AppSpacing.lg, 0),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(3),
            decoration: const BoxDecoration(
              shape: BoxShape.circle,
              color: AppColors.brandYellow,
            ),
            child: Container(
              padding: const EdgeInsets.all(2),
              decoration: BoxDecoration(
                  shape: BoxShape.circle, color: AppColors.background),
              child: UserAvatar(name: name, radius: 24),
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Text(
              hasName
                  ? l10n.homeGreetingWithName(name!)
                  : l10n.homeGreetingNoName,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style:
                  AppText.title(context).copyWith(fontSize: 22, height: 1.25),
            ),
          ),
          const NotificationBell(),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({
    required this.title,
    this.unread = 0,
    this.actionLabel,
    this.onAction,
  });

  final String title;
  final int unread;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return Padding(
      // Directional: the title side (right in Arabic/Kurdish) gets the 16 page
      // margin; the "view all" button on the other side has its own padding.
      padding: const EdgeInsetsDirectional.fromSTEB(
          AppSpacing.lg, 0, AppSpacing.xs, 4),
      child: Row(
        children: [
          Text(title, style: AppText.section(context).copyWith(fontSize: 18)),
          AnimatedSize(
            duration: AppMotion.base,
            curve: AppMotion.standard,
            child: unread > 0
                ? Container(
                    margin: const EdgeInsetsDirectional.only(start: 8),
                    padding:
                        const EdgeInsets.symmetric(horizontal: 9, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppColors.brandYellow,
                      borderRadius: BorderRadius.circular(AppRadius.pill),
                    ),
                    child: AnimatedCount(
                      value: unread,
                      style: const TextStyle(
                          color: AppColors.brandBlack,
                          fontSize: 12,
                          fontWeight: FontWeight.w800),
                    ),
                  )
                : const SizedBox.shrink(),
          ),
          const Spacer(),
          if (actionLabel != null)
            TextButton(onPressed: onAction, child: Text(actionLabel!)),
        ],
      ),
    );
  }
}

/// Swipeable vehicle cards with a slow autoplay. Autoplay pauses while the
/// user touches the carousel, and does nothing for a single vehicle. The
/// neighbouring cards recede a little so the centred one leads.
class VehicleCarousel extends StatefulWidget {
  const VehicleCarousel({
    super.key,
    required this.vehicles,
    this.autoplay = true,
  });

  final List<VehicleModel> vehicles;
  final bool autoplay;

  @override
  State<VehicleCarousel> createState() => _VehicleCarouselState();
}

class _VehicleCarouselState extends State<VehicleCarousel> {
  /// Gap kept between two neighbouring cards (half on each side of a card).
  static const double _cardGap = 6;

  PageController _controller = PageController();
  double _fraction = 1;
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

  /// A single vehicle fills the page margin exactly like every other block on
  /// the screen. With several, the same 16 px margin is kept on the leading
  /// edge and the next card peeks in on the other side.
  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _syncController();
  }

  @override
  void didUpdateWidget(covariant VehicleCarousel oldWidget) {
    super.didUpdateWidget(oldWidget);
    _syncController();
  }

  void _syncController() {
    final width = MediaQuery.sizeOf(context).width;
    final fraction = widget.vehicles.length > 1
        ? ((width - 2 * (AppSpacing.lg - _cardGap)) / width).clamp(0.5, 1.0)
        : 1.0;
    if ((fraction - _fraction).abs() < 0.001 && _controller.hasClients) return;
    if ((fraction - _fraction).abs() < 0.001) {
      _fraction = fraction;
      return;
    }
    final old = _controller;
    _fraction = fraction;
    _controller = PageController(viewportFraction: fraction, initialPage: 0);
    _page = 0;
    WidgetsBinding.instance.addPostFrameCallback((_) => old.dispose());
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
          height: 236,
          child: Listener(
            onPointerDown: (_) => _touching = true,
            onPointerUp: (_) => _touching = false,
            onPointerCancel: (_) => _touching = false,
            child: PageView.builder(
              controller: _controller,
              itemCount: count,
              onPageChanged: (i) => setState(() => _page = i),
              itemBuilder: (context, i) => AnimatedBuilder(
                animation: _controller,
                builder: (context, child) {
                  final page = _controller.hasClients &&
                          _controller.position.haveDimensions
                      ? (_controller.page ?? 0)
                      : 0.0;
                  final distance = (page - i).abs().clamp(0.0, 1.0);
                  return Transform.scale(
                    scale: 1 - 0.06 * distance,
                    child: Opacity(opacity: 1 - 0.35 * distance, child: child),
                  );
                },
                child: Padding(
                  padding: EdgeInsets.symmetric(
                      horizontal: count > 1 ? _cardGap : AppSpacing.lg),
                  child: _VehicleCard(vehicle: widget.vehicles[i]),
                ),
              ),
            ),
          ),
        ),
        if (count > 1) ...[
          const SizedBox(height: AppSpacing.md),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              for (var i = 0; i < count; i++)
                AnimatedContainer(
                  duration: AppMotion.base,
                  curve: AppMotion.standard,
                  margin: const EdgeInsets.symmetric(horizontal: 3),
                  width: i == _page ? 22 : 6,
                  height: 6,
                  decoration: BoxDecoration(
                    color: i == _page ? AppColors.onSurface : AppColors.ink200,
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
  const _VehicleCard({required this.vehicle});

  final VehicleModel vehicle;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final linked = vehicle.tags.any((t) => t.status == 'assigned');
    final statusColor =
        linked ? const Color(0xFF4CD264) : const Color(0xFFFF6B5E);

    return Pressable(
      scale: 0.98,
      onTap: () => context.push('/vehicles/${vehicle.id}'),
      child: Container(
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(
          color: AppColors.brandBlack,
          borderRadius: AppRadius.rLg,
          boxShadow: AppShadows.raised,
        ),
        child: Stack(
          children: [
            // A faint warm glow in one corner gives the black card depth.
            PositionedDirectional(
              top: -60,
              end: -40,
              child: Container(
                width: 200,
                height: 200,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(colors: [
                    AppColors.brandYellow.withValues(alpha: 0.16),
                    AppColors.brandYellow.withValues(alpha: 0),
                  ]),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: statusColor.withValues(alpha: 0.16),
                          borderRadius: BorderRadius.circular(AppRadius.pill),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                                linked
                                    ? Icons.link_rounded
                                    : Icons.link_off_rounded,
                                color: statusColor,
                                size: 17),
                            const SizedBox(width: 6),
                            Text(linked ? l10n.homeLinked : l10n.homeNotLinked,
                                style: TextStyle(
                                    color: statusColor,
                                    fontWeight: FontWeight.w800,
                                    fontSize: 12.5)),
                          ],
                        ),
                      ),
                      const Spacer(),
                      Text(vehicleTypeLabel(context, vehicle.vehicleType),
                          style: const TextStyle(
                              color: Colors.white60,
                              fontSize: 12.5,
                              fontWeight: FontWeight.w600)),
                    ],
                  ),
                  const Spacer(),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      VehicleThumb(
                        kind: thumbKindFor(
                            vehicleType: vehicle.vehicleType,
                            plateType: vehicle.plateType),
                        color: Colors.white.withValues(alpha: 0.9),
                        cutout: AppColors.brandBlack,
                        width: 84,
                      ),
                      const SizedBox(width: AppSpacing.md),
                      Expanded(
                        child: Column(
                          children: [
                            Hero(
                              tag: 'plate-${vehicle.id}',
                              child: FittedBox(
                                fit: BoxFit.scaleDown,
                                child: IraqiPlate(
                                  number: vehicle.plateNumber,
                                  letter: vehicle.plateLetter,
                                  governorate: vehicle.plateGovernorate,
                                  type: PlateType.fromCode(vehicle.plateType),
                                  width: 190,
                                ),
                              ),
                            ),
                            const SizedBox(height: 5),
                            Text(
                              plateTypeLabel(
                                  l10n, PlateType.fromCode(vehicle.plateType)),
                              style: const TextStyle(
                                  color: Colors.white54, fontSize: 11),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const Spacer(),
                  const Align(
                    alignment: AlignmentDirectional.centerEnd,
                    child: Icon(Icons.arrow_outward_rounded,
                        color: Colors.white38, size: 20),
                  ),
                ],
              ),
            ),
          ],
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

    return Pressable(
      onTap: () => context.push('/vehicles/new'),
      scale: 0.98,
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.xl),
        decoration: BoxDecoration(
          color: AppColors.brandBlack,
          borderRadius: AppRadius.rLg,
          boxShadow: AppShadows.raised,
        ),
        child: Row(
          children: [
            const CircleAvatar(
              radius: 28,
              backgroundColor: AppColors.brandYellow,
              child: Icon(Icons.add_rounded,
                  color: AppColors.brandBlack, size: 30),
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
                          fontSize: 17)),
                  const SizedBox(height: 4),
                  Text(l10n.noVehicleYetHomeSubtitle,
                      style: const TextStyle(
                          color: Colors.white70, fontSize: 13, height: 1.5)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Conversations extends StatelessWidget {
  const _Conversations({super.key, required this.items});

  final List<HomeConversation> items;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    if (items.isEmpty) {
      return Padding(
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.lg, vertical: AppSpacing.md),
        child: Row(
          children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                  color: AppColors.ink100, shape: BoxShape.circle),
              child: Icon(Icons.forum_outlined, color: AppColors.ink400),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Text(l10n.homeConversationsEmpty,
                  style:
                      AppText.body(context).copyWith(color: AppColors.ink500)),
            ),
          ],
        ),
      );
    }

    return Column(
      children: [
        for (var i = 0; i < items.length; i++)
          FadeSlideIn(
            index: i + 3,
            child: _ConversationRow(item: items[i]),
          ),
      ],
    );
  }
}

class _ConversationRow extends StatelessWidget {
  const _ConversationRow({required this.item});

  final HomeConversation item;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final c = item;
    final named = c.visitorName?.isNotEmpty == true;

    return Pressable(
      onTap: () => context.push('/contacts/${c.id}'),
      scale: 0.985,
      child: Padding(
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.lg, vertical: AppSpacing.sm + 2),
        child: Row(
          children: [
            AnimatedContainer(
              duration: AppMotion.base,
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: c.unread ? AppColors.brandYellow : AppColors.ink100,
              ),
              alignment: Alignment.center,
              child: named
                  ? Text(c.visitorName!.characters.first,
                      style: TextStyle(
                          color: c.unread
                              ? AppColors.brandBlack
                              : AppColors.ink700,
                          fontSize: 18,
                          fontWeight: FontWeight.w800))
                  : Icon(Icons.person_outline_rounded,
                      color:
                          c.unread ? AppColors.brandBlack : AppColors.ink500),
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
                          named ? c.visitorName! : l10n.visitorAnonymous,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                              fontSize: 15,
                              color: AppColors.ink900,
                              fontWeight:
                                  c.unread ? FontWeight.w800 : FontWeight.w600),
                        ),
                      ),
                      Text(notificationTime(c.at),
                          style: TextStyle(
                              color: c.unread
                                  ? AppColors.ink700
                                  : AppColors.ink400,
                              fontSize: 11.5,
                              fontWeight: FontWeight.w600)),
                    ],
                  ),
                  const SizedBox(height: 3),
                  Row(
                    children: [
                      if (c.fromOwner)
                        Padding(
                          padding: const EdgeInsetsDirectional.only(end: 4),
                          child: Transform.flip(
                              flipX: Directionality.of(context) ==
                                  TextDirection.rtl,
                              child: Icon(Icons.reply_rounded,
                                  size: 16, color: AppColors.ink400)),
                        ),
                      Expanded(
                        child: Text(
                          c.preview,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                              color: c.unread
                                  ? AppColors.ink800
                                  : AppColors.ink500,
                              fontSize: 13.5),
                        ),
                      ),
                      if (c.unread)
                        Container(
                          margin: const EdgeInsetsDirectional.only(start: 8),
                          width: 9,
                          height: 9,
                          decoration: BoxDecoration(
                              color: AppColors.onSurface,
                              shape: BoxShape.circle),
                        ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
