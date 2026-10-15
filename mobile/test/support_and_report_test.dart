import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/features/contacts/data/contact_request_model.dart';
import 'package:qreach_smarttag/features/contacts/data/contact_request_repository.dart';
import 'package:qreach_smarttag/features/contacts/presentation/contact_thread_screen.dart';
import 'package:qreach_smarttag/features/support/data/support_repository.dart';
import 'package:qreach_smarttag/features/support/presentation/support_screens.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

class _Support implements SupportRepository {
  @override
  Future<List<SupportWhatsapp>> whatsapp() async => const [
        SupportWhatsapp(
            label: 'الدعم',
            phone: '+9647701234567',
            url: 'https://wa.me/9647701234567'),
      ];

  @override
  Future<List<SupportTicketModel>> tickets() async => [
        SupportTicketModel(
          id: 't1',
          category: 'bug',
          subject: 'التطبيق يتوقف',
          status: 'answered',
          createdAt: DateTime(2026, 10, 16),
          lastMessage: 'جرّب تحديث التطبيق',
        ),
      ];

  @override
  Future<SupportTicketModel> ticket(String id) async => SupportTicketModel(
        id: id,
        category: 'bug',
        subject: 'التطبيق يتوقف',
        status: 'answered',
        createdAt: DateTime(2026, 10, 16),
        messages: [
          SupportMessageModel(
              fromStaff: false,
              body: 'يتوقف عند مسح الرمز',
              createdAt: DateTime(2026, 10, 16, 9)),
          SupportMessageModel(
              fromStaff: true,
              body: 'جرّب تحديث التطبيق',
              createdAt: DateTime(2026, 10, 16, 10)),
        ],
      );

  @override
  Future<SupportTicketModel> create(
          {required String category,
          required String subject,
          required String body,
          String? imagePath}) async =>
      ticket('new');

  @override
  Future<SupportTicketModel> reply(String id, String body,
          {String? imagePath}) async =>
      ticket(id);
}

class _Contacts implements ContactRequestRepository {
  _Contacts(this.thread);

  final ContactRequestModel thread;
  final List<String> reports = [];
  bool? identity;

  @override
  Future<ContactRequestFeed> fetch() async =>
      ContactRequestFeed(items: [thread], unreadCount: 0);

  @override
  Future<ContactRequestModel> fetchOne(String id) async => thread;

  @override
  Future<ContactRequestModel> reply(String id, String body,
          {String? imagePath}) async =>
      thread;

  @override
  Future<void> report(String id, String reason, {String? details}) async =>
      reports.add(reason);

  @override
  Future<ContactRequestModel> setIdentityVisible(
      String id, bool visible) async {
    identity = visible;
    return thread;
  }

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

void _tall(WidgetTester tester) {
  tester.view.physicalSize = const Size(800, 1800);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
}

ContactRequestModel _appThread({required String role, bool visible = false}) =>
    ContactRequestModel(
      id: 'c1',
      visitorName: null,
      visitorPhone: null,
      message: 'سيارتك تعيق الطريق',
      status: 'new',
      vehicle: 'سيارة العمل',
      createdAt: DateTime(2026, 10, 16, 9),
      expiresAt: DateTime(2026, 10, 23),
      role: role,
      senderKind: role == 'owner' ? 'app' : 'web',
      senderBadge: 'trusted',
      senderIdentityVisible: visible,
      identityVisible: visible,
    );

void main() {
  testWidgets('the support page offers WhatsApp, a new ticket and my tickets',
      (tester) async {
    _tall(tester);
    await tester.pumpWidget(_app(const SupportScreen(),
        [supportRepositoryProvider.overrideWithValue(_Support())]));
    await tester.pumpAndSettle();

    expect(find.text('الدعم'), findsOneWidget);
    expect(find.text('+9647701234567'), findsOneWidget);
    expect(find.text('فتح تذكرة جديدة'), findsOneWidget);
    expect(find.text('التطبيق يتوقف'), findsOneWidget);
    expect(find.text('تم الرد'), findsOneWidget);
  });

  testWidgets('a ticket shows both sides and lets me write and attach a photo',
      (tester) async {
    _tall(tester);
    await tester.pumpWidget(_app(const SupportThreadScreen(ticketId: 't1'),
        [supportRepositoryProvider.overrideWithValue(_Support())]));
    await tester.pumpAndSettle();

    expect(find.text('يتوقف عند مسح الرمز'), findsOneWidget);
    expect(find.text('جرّب تحديث التطبيق'), findsOneWidget);
    expect(find.byIcon(Icons.add_photo_alternate_outlined), findsOneWidget);
  });

  testWidgets('the owner sees a trust badge, not a name, and can report',
      (tester) async {
    _tall(tester);
    final repo = _Contacts(_appThread(role: 'owner'));
    await tester.pumpWidget(_app(const ContactThreadScreen(requestId: 'c1'),
        [contactRequestRepositoryProvider.overrideWithValue(repo)]));
    await tester.pumpAndSettle();

    expect(find.text('مستخدم موثوق'), findsOneWidget);

    await tester.tap(find.byType(PopupMenuButton<String>));
    await tester.pumpAndSettle();
    // The owner cannot change the sender's identity.
    expect(find.text('إظهار هويتي للمالك'), findsNothing);
    await tester.tap(find.text('إبلاغ عن مشكلة').last);
    await tester.pumpAndSettle();

    await tester.tap(find.text('مضايقة'));
    await tester.pump();
    await tester.tap(find.text('إرسال البلاغ'));
    await tester.pumpAndSettle();

    expect(repo.reports, ['harassment']);
  });

  testWidgets('the one who scanned can show or hide their identity',
      (tester) async {
    _tall(tester);
    final repo = _Contacts(_appThread(role: 'reporter'));
    await tester.pumpWidget(_app(const ContactThreadScreen(requestId: 'c1'),
        [contactRequestRepositoryProvider.overrideWithValue(repo)]));
    await tester.pumpAndSettle();

    await tester.tap(find.byType(PopupMenuButton<String>));
    await tester.pumpAndSettle();
    await tester.tap(find.text('إظهار هويتي للمالك'));
    await tester.pumpAndSettle();

    expect(repo.identity, isTrue);
  });
}
