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

/// The bottom bar: icons only, in fixed equal slots — nothing grows or moves when the
/// selection changes. Two tabs, the round scan button in the middle (raised above the bar),
/// two tabs. The selected tab is a solid disc behind its icon; the labels are kept only for
/// screen readers.
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

  static const double _barHeight = 64;
  static const double _lift = 26; // how far the scan button rises above the bar
  static const double _scanSize = 62;

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).padding.bottom;
    final half = items.length ~/ 2;

    Widget tab(int i) => Expanded(
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
        height: _barHeight + _lift,
        child: Stack(
          clipBehavior: Clip.none,
          children: [
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              height: _barHeight,
              child: Container(
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(_barHeight / 2),
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
                    // The middle belongs to the scan button.
                    const SizedBox(width: _scanSize + 16),
                    for (var i = half; i < items.length; i++) tab(i),
                  ],
                ),
              ),
            ),
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
                      width: _scanSize,
                      height: _scanSize,
                      decoration: BoxDecoration(
                        color: AppColors.brandYellow,
                        shape: BoxShape.circle,
                        border: Border.all(color: AppColors.surface, width: 4),
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
    final discColor = dark ? AppColors.brandYellow : AppColors.brandBlack;
    final onDisc = dark ? AppColors.brandBlack : AppColors.brandYellow;

    return Semantics(
      button: true,
      selected: selected,
      label: item.label,
      child: Pressable(
        onTap: onTap,
        scale: 0.92,
        child: Center(
          child: AnimatedContainer(
            duration: AppMotion.base,
            curve: AppMotion.standard,
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: selected ? discColor : Colors.transparent,
              shape: BoxShape.circle,
            ),
            child: Badge(
              isLabelVisible: item.badge > 0,
              label: Text(item.badge > 9 ? '9+' : '${item.badge}'),
              backgroundColor: AppColors.brandYellow,
              textColor: AppColors.brandBlack,
              offset: const Offset(-2, -2),
              child: Icon(
                selected ? item.selectedIcon : item.icon,
                size: 26,
                color: selected ? onDisc : AppColors.ink500,
              ),
            ),
          ),
        ),
      ),
    );
  }
}
