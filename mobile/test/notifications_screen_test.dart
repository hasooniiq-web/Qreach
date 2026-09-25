import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/features/notifications/data/notification_model.dart';
import 'package:qreach_smarttag/features/notifications/data/notification_repository.dart';
import 'package:qreach_smarttag/features/notifications/presentation/notifications_screen.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

class _FakeRepo implements NotificationRepository {
  _FakeRepo(this.items);

  List<NotificationModel> items;
  final List<String> marked = [];
  bool markedAll = false;

  int get unread => items.where((n) => !n.read).length;

  @override
  Future<NotificationFeed> fetch() async =>
      NotificationFeed(items: items, unreadCount: unread);

  @override
  Future<void> markRead(String id) async => marked.add(id);

  @override
  Future<void> markAllRead() async => markedAll = true;
}

NotificationModel _n(String id, String title,
        {String? action = 'vehicle_block',
        bool read = false,
        DateTime? at,
        String? message}) =>
    NotificationModel(
      id: id,
      type: 'tag_action',
      actionType: action,
      title: title,
      message: message,
      visitorName: null,
      vehicle: 'سيارتي',
      read: read,
      createdAt: at ?? DateTime.now(),
    );

Widget _app(_FakeRepo repo) => ProviderScope(
      overrides: [notificationRepositoryProvider.overrideWithValue(repo)],
      child: const MaterialApp(
        locale: Locale('ar'),
        supportedLocales: [Locale('ar'), Locale('en')],
        localizationsDelegates: [
          AppLocalizations.delegate,
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        home: NotificationsScreen(),
      ),
    );

void main() {
  testWidgets('empty feed shows the empty state', (tester) async {
    await tester.pumpWidget(_app(_FakeRepo([])));
    await tester.pumpAndSettle();

    expect(find.text('لا توجد إشعارات بعد'), findsOneWidget);
    expect(find.text('تحديد الكل كمقروء'), findsNothing);
  });

  testWidgets('items are grouped by day and unread ones offer mark-all-read',
      (tester) async {
    final repo = _FakeRepo([
      _n('1', 'مركبتك تعيق المرور', message: 'رجاء النقل'),
      _n('2', 'تنبيه طارئ', action: 'emergency'),
      _n('3', 'رسالة قديمة',
          read: true, at: DateTime.now().subtract(const Duration(days: 5))),
    ]);
    await tester.pumpWidget(_app(repo));
    await tester.pumpAndSettle();

    expect(find.text('اليوم'), findsOneWidget);
    expect(find.text('سابقًا'), findsOneWidget);
    expect(find.text('مركبتك تعيق المرور'), findsOneWidget);
    expect(find.textContaining('رجاء النقل'), findsOneWidget);
    expect(find.text('تحديد الكل كمقروء'), findsOneWidget);
  });

  testWidgets('tapping an unread item marks it read on the server',
      (tester) async {
    final repo = _FakeRepo([_n('1', 'مركبتك تعيق المرور')]);
    await tester.pumpWidget(_app(repo));
    await tester.pumpAndSettle();

    await tester.tap(find.text('مركبتك تعيق المرور'));
    await tester.pumpAndSettle();

    expect(repo.marked, ['1']);
    // Once nothing is unread, the mark-all action goes away.
    expect(find.text('تحديد الكل كمقروء'), findsNothing);
  });

  testWidgets('mark all as read calls the server once', (tester) async {
    final repo = _FakeRepo([_n('1', 'أ'), _n('2', 'ب')]);
    await tester.pumpWidget(_app(repo));
    await tester.pumpAndSettle();

    await tester.tap(find.text('تحديد الكل كمقروء'));
    await tester.pumpAndSettle();

    expect(repo.markedAll, isTrue);
    expect(find.text('تحديد الكل كمقروء'), findsNothing);
  });
}
