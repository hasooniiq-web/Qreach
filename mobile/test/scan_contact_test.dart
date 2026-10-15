import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/features/contacts/data/contact_request_model.dart';
import 'package:qreach_smarttag/features/contacts/data/contact_request_repository.dart';
import 'package:qreach_smarttag/features/contacts/presentation/contact_thread_screen.dart';
import 'package:qreach_smarttag/features/scan_contact/data/scan_contact_repository.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

/// A conversation the signed-in customer started by scanning someone else's code.
class _ReporterContacts implements ContactRequestRepository {
  final List<String> replies = [];

  ContactRequestModel get _thread => ContactRequestModel(
        id: 'c1',
        visitorName: null,
        visitorPhone: null,
        message: 'سيارتك تعيق الطريق',
        status: 'new',
        vehicle: 'سيارة العمل',
        createdAt: DateTime(2026, 10, 15, 9),
        expiresAt: DateTime(2026, 10, 22),
        role: 'reporter',
        reason: 'blocking',
        messages: [
          ThreadMessage(
              fromOwner: true,
              body: 'أنا قادم',
              createdAt: DateTime(2026, 10, 15, 9, 5)),
        ],
      );

  @override
  Future<ContactRequestFeed> fetch() async =>
      ContactRequestFeed(items: [_thread], unreadCount: 0);

  @override
  Future<ContactRequestModel> fetchOne(String id) async => _thread;

  @override
  Future<ContactRequestModel> reply(String id, String body,
      {String? imagePath}) async {
    replies.add(body);
    return _thread;
  }

  @override
  Future<void> report(String id, String reason, {String? details}) async {}
  @override
  Future<ContactRequestModel> setIdentityVisible(String id, bool visible) async =>
      fetchOne(id);
  @override
  Future<void> markRead(String id) async {}

  @override
  Future<void> markResponded(String id) async {}

  @override
  Future<Uint8List> fetchMessageImage(String path) async => Uint8List(0);
}

Widget _app(Widget home, List<Override> overrides) => ProviderScope(
      overrides: overrides,
      child: MaterialApp(
        locale: const Locale('ar'),
        supportedLocales: const [Locale('ar'), Locale('en')],
        localizationsDelegates: const [
          AppLocalizations.delegate,
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        home: home,
      ),
    );

void main() {
  group('a scanned code', () {
    test('is read from the server answer', () {
      final v = ScannedVehicle.fromJson({
        'token': 'ABC123',
        'vehicle': {'nickname': 'سيارة العمل', 'make': 'Toyota', 'vehicle_type': 'sedan'},
        'owner_name': 'أحمد',
        'contacts': [
          {'label': 'هاتف', 'value': '0770', 'href': 'tel:0770'}
        ],
        'conversation_id': 'c1',
      });

      expect(v.title, 'سيارة العمل');
      expect(v.ownerName, 'أحمد');
      expect(v.contacts.single.href, 'tel:0770');
      expect(v.conversationId, 'c1');
      expect(scanReasons.length, 6);
    });

    test('with no nickname falls back to make and model', () {
      final v = ScannedVehicle.fromJson({
        'token': 'T',
        'vehicle': {'make': 'Toyota', 'model': 'Camry', 'nickname': null},
      });
      expect(v.title, 'Toyota Camry');
    });
  });

  group('the conversation I started', () {
    test('role and reason come from the server; "new" is not mine to act on', () {
      final r = ContactRequestModel.fromJson({
        'id': 'c1',
        'role': 'reporter',
        'reason': 'lights',
        'message': 'أضواء المركبة مشتعلة',
        'status': 'new',
        'created_at': '2026-10-15T09:00:00Z',
      });

      expect(r.isReporter, isTrue);
      expect(r.isNew, isFalse);
      expect(r.reason, 'lights');

      final owner = ContactRequestModel.fromJson({
        'id': 'c2',
        'message': 'x',
        'status': 'new',
        'created_at': '2026-10-15T09:00:00Z',
      });
      expect(owner.isReporter, isFalse);
      expect(owner.isNew, isTrue);
    });

    testWidgets('shows the vehicle, my first message on the right and can answer',
        (tester) async {
      tester.view.physicalSize = const Size(800, 1600);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);

      final repo = _ReporterContacts();
      await tester.pumpWidget(_app(const ContactThreadScreen(requestId: 'c1'),
          [contactRequestRepositoryProvider.overrideWithValue(repo)]));
      await tester.pumpAndSettle();

      expect(find.text('سيارة العمل'), findsOneWidget);
      expect(find.text('محادثة بدأتَها بعد مسح رمز المركبة'), findsOneWidget);

      // Mine (the reason I sent) is on the right; the owner's answer on the left.
      final mine = tester.getTopRight(find.text('سيارتك تعيق الطريق'));
      final theirs = tester.getTopLeft(find.text('أنا قادم'));
      expect(mine.dx, greaterThan(theirs.dx));

      await tester.enterText(find.byType(TextField), 'شكرًا');
      await tester.pump();
      await tester.tap(find.byIcon(Icons.send_rounded));
      await tester.pumpAndSettle();
      expect(repo.replies, ['شكرًا']);
    });
  });
}
