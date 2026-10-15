import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/design/motion.dart';
import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';
import '../../l10n/app_localizations.dart';
import '../contacts/presentation/contact_controller.dart';
import '../home/presentation/home_controller.dart';
import '../notifications/presentation/notification_controller.dart';

/// The four top-level tabs: Home, Vehicles, Contacts, Profile. Each tab
/// keeps its own scroll position and navigation stack; detail screens
/// (vehicle, scan, notifications, subscription...) open full-screen over it.
class AppShell extends ConsumerStatefulWidget {
  const AppShell({super.key, required this.navigationShell});

  final StatefulNavigationShell navigationShell;

  @override
  ConsumerState<AppShell> createState() => _AppShellState();
}

/// Keeps the app current without any manual refresh: while the app is in the
/// foreground it re-reads notifications, conversations and the home feed every
/// few seconds, and again the moment the app returns from the background.
/// (True push needs the Firebase server key; until then this is the
/// near-real-time path.)
class _AppShellState extends ConsumerState<AppShell>
    with WidgetsBindingObserver {
  static const _interval = Duration(seconds: 12);
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _timer = Timer.periodic(_interval, (_) => _refresh());
  }

  void _refresh() {
    if (!mounted) return;
    ref.invalidate(notificationsProvider);
    ref.invalidate(contactsProvider);
    ref.invalidate(homeProvider);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _refresh();
  }

  @override
  void dispose() {
    _timer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  StatefulNavigationShell get navigationShell => widget.navigationShell;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final newMessages =
        ref.watch(contactsProvider).valueOrNull?.unreadCount ?? 0;

    final items = [
      _NavItem(Icons.home_outlined, Icons.home_rounded, l10n.tabHome),
      _NavItem(Icons.directions_car_outlined, Icons.directions_car_rounded,
          l10n.tabVehicles),
      _NavItem(Icons.forum_outlined, Icons.forum_rounded, l10n.tabContacts,
          badge: newMessages),
      _NavItem(
          Icons.person_outline_rounded, Icons.person_rounded, l10n.tabProfile),
    ];

    return Scaffold(
      extendBody: true,
      // Switching tabs fades the new tab in instead of cutting to it.
      body: _TabFade(
        index: navigationShell.currentIndex,
        child: navigationShell,
      ),
      bottomNavigationBar: _AppNavBar(
        items: items,
        scanLabel: l10n.navScanQr,
        onScan: () {
          HapticFeedback.mediumImpact();
          context.push('/scan');
        },
        selected: navigationShell.currentIndex,
        onSelected: (index) => navigationShell.goBranch(
          index,
          // Tapping the active tab returns to its root.
          initialLocation: index == navigationShell.currentIndex,
        ),
      ),
    );
  }
}

class _NavItem {
  const _NavItem(this.icon, this.selectedIcon, this.label, {this.badge = 0});

  final IconData icon;
  final IconData selectedIcon;
  final String label;
  final int badge;
}

/// Replays a short fade whenever the selected tab changes; the tab's own
/// widget (and its scroll position) is kept, only the reveal is animated.
class _TabFade extends StatefulWidget {
  const _TabFade({required this.index, required this.child});

  final int index;
  final Widget child;

  @override
  State<_TabFade> createState() => _TabFadeState();
}

class _TabFadeState extends State<_TabFade>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: AppMotion.base,
    value: 1,
  );

  @override
  void didUpdateWidget(_TabFade old) {
    super.didUpdateWidget(old);
    if (old.index != widget.index) _controller.forward(from: 0.35);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: CurvedAnimation(parent: _controller, curve: AppMotion.enter),
      child: widget.child,
    );
  }
}

/// A floating navigation bar. The selected destination grows into a dark pill
/// that shows its label, animating its width as selection moves; the others
/// stay as quiet icons. Icons cross-fade between outlined and filled.
class _AppNavBar extends StatelessWidget {
  const _AppNavBar({
    required this.items,
    required this.selected,
    required this.onSelected,
    required this.scanLabel,
    required this.onScan,
  });

  final List<_NavItem> items;
  final int selected;
  final ValueChanged<int> onSelected;
  final String scanLabel;
  final VoidCallback onScan;

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).padding.bottom;
    const barHeight = 68.0;
    const lift = 28.0; // how far the round scan button rises above the bar
    final half = items.length ~/ 2;

    Widget tab(int i) => Expanded(
          flex: i == selected ? 5 : 3,
          child: _NavButton(
            item: items[i],
            selected: i == selected,
            onTap: () {
              if (i != selected) HapticFeedback.selectionClick();
              onSelected(i);
            },
          ),
        );

    return Padding(
      padding:
          EdgeInsets.fromLTRB(16, 0, 16, 12 + (bottom > 0 ? bottom - 8 : 0)),
      child: SizedBox(
        height: barHeight + lift,
        child: Stack(
          clipBehavior: Clip.none,
          children: [
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              height: barHeight,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(34),
                  boxShadow: AppColors.dark
                      ? const []
                      : const [
                          BoxShadow(
                              color: Color(0x22000000),
                              blurRadius: 30,
                              offset: Offset(0, 10)),
                        ],
                  border: AppShadows.hairline,
                ),
                child: Row(
                  children: [
                    for (var i = 0; i < half; i++) tab(i),
                    // The middle is the floating scan button's place.
                    const SizedBox(width: 78),
                    for (var i = half; i < items.length; i++) tab(i),
                  ],
                ),
              ),
            ),
            // The floating "scan a vehicle code" button, centred on the bar.
            Positioned(
              top: 0,
              left: 0,
              right: 0,
              child: Center(
                child: Semantics(
                  button: true,
                  label: scanLabel,
                  child: GestureDetector(
                    behavior: HitTestBehavior.opaque,
                    onTap: onScan,
                    child: Container(
                      width: 64,
                      height: 64,
                      decoration: BoxDecoration(
                        color: AppColors.brandYellow,
                        shape: BoxShape.circle,
                        border:
                            Border.all(color: AppColors.surface, width: 4),
                        boxShadow: const [
                          BoxShadow(
                              color: Color(0x33000000),
                              blurRadius: 16,
                              offset: Offset(0, 6)),
                        ],
                      ),
                      child: const Icon(Icons.qr_code_scanner_rounded,
                          size: 30, color: AppColors.brandBlack),
                    ),
                  ),
                ),
              ),
            ),
            Positioned(
              bottom: 8,
              left: 0,
              right: 0,
              child: IgnorePointer(
                child: Text(
                  scanLabel,
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  style: TextStyle(
                      color: AppColors.ink700,
                      fontSize: 11.5,
                      fontWeight: FontWeight.w800),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _NavButton extends StatelessWidget {
  const _NavButton({
    required this.item,
    required this.selected,
    required this.onTap,
  });

  final _NavItem item;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final dark = AppColors.dark;
    final pillColor = dark ? AppColors.brandYellow : AppColors.brandBlack;
    final onPill = dark ? AppColors.brandBlack : AppColors.brandYellow;

    return Semantics(
      button: true,
      selected: selected,
      label: item.label,
      child: Pressable(
        onTap: onTap,
        scale: 0.94,
        child: Center(
          child: AnimatedContainer(
            duration: AppMotion.base,
            curve: AppMotion.standard,
            height: 48,
            padding: EdgeInsets.symmetric(horizontal: selected ? 14 : 0),
            decoration: BoxDecoration(
              color: selected ? pillColor : Colors.transparent,
              borderRadius: BorderRadius.circular(24),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Badge(
                  isLabelVisible: item.badge > 0,
                  label: Text(item.badge > 9 ? '9+' : '${item.badge}'),
                  backgroundColor: AppColors.brandYellow,
                  textColor: AppColors.brandBlack,
                  offset: const Offset(-4, -4),
                  child: AnimatedSwitcher(
                    duration: AppMotion.fast,
                    transitionBuilder: (c, a) => ScaleTransition(
                        scale: a, child: FadeTransition(opacity: a, child: c)),
                    child: Icon(
                      selected ? item.selectedIcon : item.icon,
                      key: ValueKey(selected),
                      size: 25,
                      color: selected ? onPill : AppColors.ink500,
                    ),
                  ),
                ),
                // The label slides open only for the selected item.
                Flexible(
                  child: AnimatedSize(
                    duration: AppMotion.base,
                    curve: AppMotion.standard,
                    alignment: AlignmentDirectional.centerStart,
                    child: selected
                        ? Padding(
                            padding: const EdgeInsetsDirectional.only(start: 8),
                            child: Text(
                              item.label,
                              maxLines: 1,
                              overflow: TextOverflow.fade,
                              softWrap: false,
                              style: TextStyle(
                                  color: onPill,
                                  fontSize: 13.5,
                                  fontWeight: FontWeight.w800),
                            ),
                          )
                        : const SizedBox.shrink(),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
