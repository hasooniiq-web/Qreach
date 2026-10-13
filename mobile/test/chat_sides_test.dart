import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/features/contacts/data/contact_request_model.dart';
import 'package:qreach_smarttag/features/contacts/data/contact_request_repository.dart';
import 'package:qreach_smarttag/features/contacts/presentation/contact_thread_screen.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

class _Repo implements ContactRequestRepository {
  final model = ContactRequestModel(
    id: 'abc',
    visitorName: 'زائر',
    visitorPhone: null,
    message: 'رسالة الزائر الأولى',
    status: 'responded',
    vehicle: 'سيارتي',
    createdAt: DateTime.utc(2026, 9, 25, 10),
    expiresAt: DateTime.utc(2026, 10, 10),
    messages: [
      ThreadMessage(
          fromOwner: false,
          body: 'رسالة الزائر الثانية',
          createdAt: DateTime.utc(2026, 9, 25, 10, 5)),
      ThreadMessage(
          fromOwner: true,
          body: 'رد المالك',
          createdAt: DateTime.utc(2026, 9, 25, 10, 6)),
    ],
  );

  @override
  Future<ContactRequestModel> fetchOne(String id) async => model;

  @override
  Future<ContactRequestModel> reply(String id, String body,
          {String? imagePath}) async =>
      model;

  @override
  Future<ContactRequestFeed> fetch() async =>
      ContactRequestFeed(items: [model], unreadCount: 0);

  @override
  Future<void> markRead(String id) async {}

  @override
  Future<void> markResponded(String id) async {}

  @override
  Future<Uint8List> fetchMessageImage(String path) async =>
      Uint8List.fromList(const []);
}

void main() {
  for (final language in ['ar', 'en']) {
    testWidgets('the visitor is on the left and the owner on the right ($language)',
        (tester) async {
      tester.view.physicalSize = const Size(800, 1600);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);

      await tester.pumpWidget(ProviderScope(
        overrides: [contactRequestRepositoryProvider.overrideWithValue(_Repo())],
        child: MaterialApp(
          locale: Locale(language),
          supportedLocales: const [Locale('ar'), Locale('en')],
          localizationsDelegates: const [
            AppLocalizations.delegate,
            GlobalMaterialLocalizations.delegate,
            GlobalWidgetsLocalizations.delegate,
            GlobalCupertinoLocalizations.delegate,
          ],
          home: const ContactThreadScreen(requestId: 'abc'),
        ),
      ));
      await tester.pumpAndSettle();

      final visitor = tester.getCenter(find.text('رسالة الزائر الثانية'));
      final owner = tester.getCenter(find.text('رد المالك'));
      final middle = tester.view.physicalSize.width / 2;

      expect(visitor.dx, lessThan(middle), reason: 'visitor bubble on the left');
      expect(owner.dx, greaterThan(middle), reason: 'owner bubble on the right');
      expect(visitor.dx, lessThan(owner.dx));
    });
  }
}
