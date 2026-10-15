import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/features/contacts/data/contact_request_model.dart';
import 'package:qreach_smarttag/features/contacts/data/contact_request_repository.dart';
import 'package:qreach_smarttag/features/contacts/presentation/contact_thread_screen.dart';
import 'package:qreach_smarttag/features/tags/data/nfc_reader.dart';
import 'package:qreach_smarttag/features/tags/data/tag_repository.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

class FakeContactRepository implements ContactRequestRepository {
  ContactRequestModel model = ContactRequestModel(
    id: 'abc',
    visitorName: 'زائر',
    visitorPhone: null,
    message: 'سيارتك تعيق الطريق',
    status: 'new',
    vehicle: 'سيارتي',
    createdAt: DateTime.utc(2026, 9, 25, 10),
    expiresAt: DateTime.utc(2026, 10, 10),
  );
  final List<String> replies = [];

  @override
  Future<ContactRequestModel> fetchOne(String id) async => model;

  @override
  Future<ContactRequestModel> reply(String id, String body,
      {String? imagePath}) async {
    replies.add(body);
    model = ContactRequestModel(
      id: model.id,
      visitorName: model.visitorName,
      visitorPhone: null,
      message: model.message,
      status: 'responded',
      vehicle: model.vehicle,
      createdAt: model.createdAt,
      expiresAt: model.expiresAt,
      messages: [
        ThreadMessage(
            fromOwner: true,
            body: body,
            createdAt: DateTime.utc(2026, 9, 25, 11))
      ],
    );
    return model;
  }

  @override
  Future<ContactRequestFeed> fetch() async =>
      ContactRequestFeed(items: [model], unreadCount: 0);

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
  Future<Uint8List> fetchMessageImage(String path) async =>
      Uint8List.fromList(const []);
}

Uint8List _bytes(List<int> v) => Uint8List.fromList(v);

void main() {
  group('NfcReader.linkFromPayload', () {
    test('decodes a URI record with the http:// prefix code', () {
      final payload =
          _bytes([0x03, ...utf8.encode('192.168.1.5:8001/t/abc123')]);
      final link = NfcReader.linkFromPayload(_bytes([0x55]), payload);

      expect(link, 'http://192.168.1.5:8001/t/abc123');
      expect(TagRepository.extractToken(link!), 'abc123');
    });

    test('decodes https:// and a plain text record', () {
      expect(
          NfcReader.linkFromPayload(_bytes([0x55]),
              _bytes([0x04, ...utf8.encode('q.example/t/xyz')])),
          'https://q.example/t/xyz');
      expect(
          NfcReader.linkFromPayload(_bytes([0x54]),
              _bytes([0x02, 0x65, 0x6e, ...utf8.encode('hello')])),
          'hello');
    });

    test('ignores unknown record types and empty payloads', () {
      expect(NfcReader.linkFromPayload(_bytes([0x58]), _bytes([1, 2])), isNull);
      expect(NfcReader.linkFromPayload(_bytes([0x55]), _bytes([])), isNull);
    });
  });

  testWidgets('owner opens a conversation, sees the message and can reply',
      (tester) async {
    tester.view.physicalSize = const Size(800, 1600);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    final repo = FakeContactRepository();
    await tester.pumpWidget(ProviderScope(
      overrides: [contactRequestRepositoryProvider.overrideWithValue(repo)],
      child: const MaterialApp(
        locale: Locale('ar'),
        supportedLocales: [Locale('ar'), Locale('en')],
        localizationsDelegates: [
          AppLocalizations.delegate,
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        home: ContactThreadScreen(requestId: 'abc'),
      ),
    ));
    await tester.pumpAndSettle();

    expect(find.text('سيارتك تعيق الطريق'), findsOneWidget);
    expect(find.textContaining('2026-10-10'), findsOneWidget,
        reason: 'the auto-delete date is shown');

    await tester.enterText(find.byType(TextField), 'أنا قادم');
    await tester.pump(); // the send button enables once there is text
    await tester.tap(find.byIcon(Icons.send_rounded));
    await tester.pumpAndSettle();

    expect(repo.replies, ['أنا قادم']);
    expect(find.text('أنا قادم'), findsOneWidget);
  });
}
