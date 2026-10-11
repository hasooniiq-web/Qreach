import 'dart:io';
import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter/services.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/core/theme/app_colors.dart';
import 'package:qreach_smarttag/core/theme/app_theme.dart';
import 'package:qreach_smarttag/features/contacts/data/contact_request_model.dart';
import 'package:qreach_smarttag/features/contacts/data/contact_request_repository.dart';
import 'package:qreach_smarttag/features/home/data/home_models.dart';
import 'package:qreach_smarttag/features/home/data/home_repository.dart';
import 'package:qreach_smarttag/features/notifications/data/notification_model.dart';
import 'package:qreach_smarttag/features/notifications/data/notification_repository.dart';
import 'package:qreach_smarttag/features/profile/data/profile_models.dart';
import 'package:qreach_smarttag/features/profile/data/profile_repository.dart';
import 'package:qreach_smarttag/features/subscription/data/subscription_models.dart';
import 'package:qreach_smarttag/features/subscription/data/subscription_repository.dart';
import 'package:qreach_smarttag/features/tags/data/tag_model.dart';
import 'package:qreach_smarttag/features/vehicles/data/vehicle_model.dart';
import 'package:qreach_smarttag/features/vehicles/data/vehicle_repository.dart';
import 'package:qreach_smarttag/l10n/app_localizations.dart';

/// Loads the real fonts so preview screenshots show Arabic text and icons
/// instead of test placeholder boxes.
Future<void> loadFonts() async {
  final noto = FontLoader('Noto Sans Arabic')
    ..addFont(rootBundle.load('assets/fonts/NotoSansArabic.ttf'));
  await noto.load();

  final icons = File(
      'C:/flutter/bin/cache/artifacts/material_fonts/MaterialIcons-Regular.otf');
  if (icons.existsSync()) {
    final loader = FontLoader('MaterialIcons')
      ..addFont(Future.value(ByteData.sublistView(icons.readAsBytesSync())));
    await loader.load();
  }
}

const _tag = TagModel(
  token: 't',
  serialNumber: 'ST-Q-0001',
  type: 'qr',
  status: 'assigned',
  publicUrl: 'http://192.168.68.103:8001/t/abc',
);

const sampleVehicles = [
  VehicleModel(
    id: '1',
    plateNumber: '48291',
    plateLetter: 'ب',
    plateGovernorate: 'بغداد',
    plateType: 'private',
    nickname: 'سيارة العائلة',
    make: 'تويوتا',
    model: 'كامري',
    vehicleType: 'sedan',
    tags: [_tag],
  ),
  VehicleModel(
    id: '2',
    plateNumber: '7710',
    plateLetter: 'ح',
    plateGovernorate: 'البصرة',
    plateType: 'taxi',
    make: 'هيونداي',
    model: 'النترا',
    vehicleType: 'sedan',
  ),
  VehicleModel(
    id: '3',
    plateNumber: '15530',
    plateGovernorate: 'أربيل',
    plateType: 'truck',
    make: 'إيسوزو',
    vehicleType: 'truck',
    tags: [_tag],
  ),
];

class FakeVehicles implements VehicleRepository {
  FakeVehicles([this.items = sampleVehicles]);
  final List<VehicleModel> items;

  @override
  Future<List<VehicleModel>> list() async => items;
  @override
  Future<VehicleModel> show(String id) async =>
      items.firstWhere((v) => v.id == id, orElse: () => items.first);
  @override
  Future<VehicleModel> create(VehicleModel vehicle) async => vehicle;
  @override
  Future<VehicleModel> update(String id, VehicleModel vehicle) async => vehicle;
  @override
  Future<void> delete(String id) async {}
  @override
  Future<void> setStatus(String id, {required bool active}) async {}
  @override
  Future<void> removeTag(String vehicleId) async {}
}

final _now = DateTime.now();

class FakeHome implements HomeRepository {
  FakeHome({this.empty = false});
  final bool empty;

  @override
  Future<HomeData> fetch() async => empty
      ? const HomeData()
      : HomeData(
          unreadMessages: 2,
          conversations: [
            HomeConversation(
                id: 'a',
                visitorName: 'أحمد',
                vehicle: 'سيارة العائلة',
                preview: 'سيارتك تعيق الطريق، هل يمكنك تحريكها؟',
                fromOwner: false,
                unread: true,
                at: _now.subtract(const Duration(minutes: 4))),
            HomeConversation(
                id: 'b',
                visitorName: null,
                vehicle: 'سيارة العائلة',
                preview: 'أنا قادم الآن',
                fromOwner: true,
                unread: false,
                at: _now.subtract(const Duration(hours: 3))),
            HomeConversation(
                id: 'c',
                visitorName: 'زينب',
                vehicle: 'هيونداي',
                preview: 'يوجد ضرر في الباب الخلفي',
                fromOwner: false,
                unread: false,
                at: _now.subtract(const Duration(days: 2))),
          ],
        );
}

class FakeNotifications implements NotificationRepository {
  @override
  Future<NotificationFeed> fetch() async => NotificationFeed(
        unreadCount: 3,
        items: [
          NotificationModel(
              id: '1',
              type: 'contact_request',
              actionType: null,
              title: 'رسالة جديدة بخصوص مركبتك',
              message: 'سيارتك تعيق الطريق',
              visitorName: 'أحمد',
              vehicle: 'سيارة العائلة',
              read: false,
              createdAt: _now.subtract(const Duration(minutes: 4))),
          NotificationModel(
              id: '2',
              type: 'tag_action',
              actionType: 'emergency',
              title: 'تنبيه طارئ',
              message: null,
              visitorName: null,
              vehicle: 'هيونداي',
              read: true,
              createdAt: _now.subtract(const Duration(days: 1))),
        ],
      );
  @override
  Future<void> markRead(String id) async {}
  @override
  Future<void> markAllRead() async {}
}

class FakeContacts implements ContactRequestRepository {
  FakeContacts({this.empty = false});
  final bool empty;

  ContactRequestModel _one(
          String id, String name, String msg, String status, Duration ago) =>
      ContactRequestModel(
        id: id,
        visitorName: name,
        visitorPhone: null,
        message: msg,
        status: status,
        vehicle: 'سيارة العائلة',
        createdAt: _now.subtract(ago),
        expiresAt: _now.add(const Duration(days: 12)),
        messages: [
          ThreadMessage(
              fromOwner: true,
              body: 'أنا قادم الآن',
              createdAt: _now.subtract(ago - const Duration(minutes: 2))),
        ],
      );

  @override
  Future<ContactRequestFeed> fetch() async => empty
      ? const ContactRequestFeed(items: [], unreadCount: 0)
      : ContactRequestFeed(unreadCount: 1, items: [
          _one('a', 'أحمد', 'سيارتك تعيق الطريق', 'new',
              const Duration(minutes: 4)),
          _one('b', '', 'لاحظت ضررًا في المركبة', 'responded',
              const Duration(hours: 5)),
          _one(
              'c', 'زينب', 'أحتاج إلى مساعدة', 'read', const Duration(days: 2)),
        ]);
  @override
  Future<ContactRequestModel> fetchOne(String id) async => _one(
      id, 'أحمد', 'سيارتك تعيق الطريق', 'read', const Duration(minutes: 9));
  @override
  Future<ContactRequestModel> reply(String id, String body,
          {String? imagePath}) async =>
      fetchOne(id);
  @override
  Future<void> markRead(String id) async {}
  @override
  Future<void> markResponded(String id) async {}
  @override
  Future<Uint8List> fetchMessageImage(String path) async =>
      Uint8List.fromList(const []);
}

class FakeProfile implements ProfileRepository {
  FakeProfile({this.plus = false});
  final bool plus;

  ProfileModel get _p => ProfileModel(
        name: 'حسين علي',
        phone: '+9647701234567',
        plan: plus ? 'plus' : 'basic',
        phoneField: const ContactField(
            value: '+9647701234567', visible: true, canShow: true),
        showName: true,
        canAddMethods: plus,
        platforms: const [
          PlatformInfo(type: 'instagram', label: 'إنستغرام', hint: 'user'),
        ],
        methods: plus
            ? const [
                ContactMethodModel(
                    id: 1,
                    type: 'instagram',
                    label: 'إنستغرام',
                    value: 'my.car',
                    visible: true,
                    canShow: true)
              ]
            : const [],
        emergency: const EmergencyModel(
            name: 'الوالد',
            relationship: 'أب',
            phone: '+9647800000001',
            visible: false,
            canShow: true),
      );

  @override
  Future<ProfileModel> fetch() async => _p;
  @override
  Future<Uint8List?> fetchAvatar() async => null;
  @override
  Future<ProfileModel> uploadAvatar(String filePath) async => _p;
  @override
  Future<ProfileModel> removeAvatar() async => _p;
  @override
  Future<ProfileModel> updateName(String name) async => _p;
  @override
  Future<ProfileModel> updatePassword(
          {String? currentPassword, required String password}) async =>
      _p;
  @override
  Future<ProfileModel> setShowName(bool value) async => _p;
  @override
  Future<ProfileModel> setShowAvatar(bool value) async => _p;
  @override
  Future<ProfileModel> updatePhone(
          {required String phone, required bool visible}) async =>
      _p;
  @override
  Future<ProfileModel> addMethod(
          {required String type,
          required String value,
          String? label,
          bool visible = false}) async =>
      _p;
  @override
  Future<ProfileModel> setMethodVisible(int id, bool visible) async => _p;
  @override
  Future<ProfileModel> removeMethod(int id) async => _p;
  @override
  Future<ProfileModel> saveEmergency(
          {required String name,
          required String? relationship,
          required String phone,
          required bool visible}) async =>
      _p;
  @override
  Future<ProfileModel> removeEmergency() async => _p;
}

const _basicPlan = PlanModel(
  slug: 'basic',
  name: 'الأساسية',
  description: 'استخدام التطبيق والرسائل',
  price: 0,
  currency: 'IQD',
  isFree: true,
  billingPeriodDays: 365,
  publicContacts: ['phone'],
);
const _plusPlan = PlanModel(
  slug: 'plus',
  name: 'بلس',
  description: 'كل ما في الأساسية وحسابات التواصل',
  price: 25000,
  currency: 'IQD',
  isFree: false,
  billingPeriodDays: 365,
  publicContacts: ['phone', 'instagram', 'other'],
  extraContactMethods: true,
);

class FakeSubscription implements SubscriptionRepository {
  @override
  Future<SubscriptionInfo> fetch() async => const SubscriptionInfo(
        plan: _basicPlan,
        isDefault: true,
        subscriptionStatus: null,
        endsAt: null,
        plans: [_basicPlan, _plusPlan],
        pendingUpgrade: false,
      );
  @override
  Future<void> requestUpgrade(String planSlug) async {}

  @override
  Future<void> cancelRenewal() async {}

  @override
  Future<void> resumeRenewal() async {}
}

List<Override> previewOverrides({
  bool emptyHome = false,
  bool plus = false,
  List<VehicleModel>? vehicles,
}) =>
    [
      vehicleRepositoryProvider
          .overrideWithValue(FakeVehicles(vehicles ?? sampleVehicles)),
      homeRepositoryProvider.overrideWithValue(FakeHome(empty: emptyHome)),
      notificationRepositoryProvider.overrideWithValue(FakeNotifications()),
      contactRequestRepositoryProvider
          .overrideWithValue(FakeContacts(empty: emptyHome)),
      profileRepositoryProvider.overrideWithValue(FakeProfile(plus: plus)),
      subscriptionRepositoryProvider.overrideWithValue(FakeSubscription()),
    ];

/// Pumps [screen] inside the real theme, localisation and a stub router at a
/// given phone size, waits for the animations, then writes a PNG.
Future<void> shoot(
  WidgetTester tester, {
  required Widget screen,
  required String name,
  Size size = const Size(390, 844),
  bool dark = false,
  String locale = 'ar',
  List<Override>? overrides,
  Future<void> Function(WidgetTester)? interact,
}) async {
  tester.view.physicalSize = size * 2;
  tester.view.devicePixelRatio = 2;
  addTearDown(tester.view.reset);

  final theme = AppTheme.build(dark: dark);
  final key = GlobalKey();
  final router = GoRouter(routes: [
    GoRoute(path: '/', builder: (_, __) => screen),
    GoRoute(
        path: '/:any(.*)', builder: (_, s) => Scaffold(body: Text('${s.uri}'))),
  ]);

  await tester.pumpWidget(ProviderScope(
    overrides: overrides ?? previewOverrides(),
    child: RepaintBoundary(
      key: key,
      child: MaterialApp.router(
        debugShowCheckedModeBanner: false,
        theme: theme,
        locale: Locale(locale),
        supportedLocales: const [Locale('ar'), Locale('en')],
        localizationsDelegates: const [
          AppLocalizations.delegate,
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        routerConfig: router,
        builder: (context, child) {
          AppColors.dark = dark;
          return child!;
        },
      ),
    ),
  ));

  // Let data futures resolve, then the entrance animations play out.
  await tester.pump(const Duration(milliseconds: 100));
  await tester.pump(const Duration(milliseconds: 100));
  if (interact != null) await interact(tester);
  await tester.pump(const Duration(milliseconds: 900));

  final boundary = tester.renderObject<RenderRepaintBoundary>(find.byKey(key));
  final bytes = await tester.runAsync(() async {
    final image = await boundary.toImage(pixelRatio: 1);
    final data = await image.toByteData(format: ui.ImageByteFormat.png);
    return data!.buffer.asUint8List();
  });
  final dir = Directory('build/previews')..createSync(recursive: true);
  File('${dir.path}/$name.png').writeAsBytesSync(bytes!);
  AppColors.dark = false;
}
