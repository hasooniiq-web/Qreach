import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/features/contacts/presentation/contacts_screen.dart';
import 'package:qreach_smarttag/features/home/presentation/home_screen.dart';
import 'package:qreach_smarttag/features/profile/presentation/profile_screen.dart';
import 'package:qreach_smarttag/features/vehicles/presentation/vehicle_list_screen.dart';

import '../support/preview_support.dart';

// Not part of the regular suite's assertions: renders key screens to PNGs in
// build/previews so the design can be reviewed without a device.
void main() {
  setUpAll(loadFonts);

  testWidgets('home', (tester) async {
    await shoot(tester, screen: const HomeScreen(), name: 'home');
  });
  testWidgets('home dark', (tester) async {
    await shoot(tester,
        screen: const HomeScreen(), name: 'home_dark', dark: true);
  });
  testWidgets('home small', (tester) async {
    await shoot(tester,
        screen: const HomeScreen(),
        name: 'home_small',
        size: const Size(360, 640));
  });
  testWidgets('vehicles', (tester) async {
    await shoot(tester, screen: const VehicleListScreen(), name: 'vehicles');
  });
  testWidgets('contacts', (tester) async {
    await shoot(tester, screen: const ContactsScreen(), name: 'contacts');
  });
  testWidgets('profile', (tester) async {
    await shoot(tester, screen: const ProfileScreen(), name: 'profile');
  });
}
