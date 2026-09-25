import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../l10n/app_localizations.dart';
import '../contacts/presentation/contact_controller.dart';

/// The four top-level tabs: Home, Vehicles, Contacts, Profile. Each tab
/// keeps its own scroll position and navigation stack; detail screens
/// (vehicle, scan, notifications, subscription...) open full-screen over it.
class AppShell extends ConsumerWidget {
  const AppShell({super.key, required this.navigationShell});

  final StatefulNavigationShell navigationShell;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final newMessages =
        ref.watch(contactsProvider).valueOrNull?.unreadCount ?? 0;

    return Scaffold(
      body: navigationShell,
      bottomNavigationBar: NavigationBarTheme(
        data: NavigationBarThemeData(
          height: 68,
          backgroundColor: AppColors.brandWhite,
          indicatorColor: AppColors.brandYellow,
          surfaceTintColor: Colors.transparent,
          labelTextStyle: WidgetStateProperty.resolveWith((states) => TextStyle(
                fontSize: 12,
                fontWeight: states.contains(WidgetState.selected)
                    ? FontWeight.w800
                    : FontWeight.w500,
                color: AppColors.brandBlack,
              )),
          iconTheme: WidgetStateProperty.all(
              const IconThemeData(color: AppColors.brandBlack)),
        ),
        child: DecoratedBox(
          decoration: const BoxDecoration(
            border: Border(top: BorderSide(color: AppColors.ink100)),
          ),
          child: NavigationBar(
            selectedIndex: navigationShell.currentIndex,
            onDestinationSelected: (index) => navigationShell.goBranch(
              index,
              // Tapping the active tab returns to its root.
              initialLocation: index == navigationShell.currentIndex,
            ),
            destinations: [
              NavigationDestination(
                icon: const Icon(Icons.home_outlined),
                selectedIcon: const Icon(Icons.home_rounded),
                label: l10n.tabHome,
              ),
              NavigationDestination(
                icon: const Icon(Icons.directions_car_outlined),
                selectedIcon: const Icon(Icons.directions_car_rounded),
                label: l10n.tabVehicles,
              ),
              NavigationDestination(
                icon: Badge(
                  isLabelVisible: newMessages > 0,
                  label: Text(newMessages > 9 ? '9+' : '$newMessages'),
                  backgroundColor: AppColors.brandBlack,
                  textColor: AppColors.brandWhite,
                  child: const Icon(Icons.forum_outlined),
                ),
                selectedIcon: const Icon(Icons.forum_rounded),
                label: l10n.tabContacts,
              ),
              NavigationDestination(
                icon: const Icon(Icons.person_outline_rounded),
                selectedIcon: const Icon(Icons.person_rounded),
                label: l10n.tabProfile,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
