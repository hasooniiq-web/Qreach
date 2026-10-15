import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../features/support/data/support_repository.dart';
import '../features/scan_contact/data/scan_contact_repository.dart';

import '../features/auth/presentation/auth_controller.dart';
import 'auth/biometric_service.dart';
import 'locale/locale_controller.dart';
import 'network/api_client.dart';
import 'notifications/push_notification_service.dart';
import 'storage/secure_storage_service.dart';
import '../features/auth/data/auth_repository.dart';
import '../features/contacts/data/contact_request_repository.dart';
import '../features/home/data/home_repository.dart';
import '../features/notifications/data/notification_repository.dart';
import '../features/profile/data/profile_repository.dart';
import '../features/referral/data/referral_repository.dart';
import '../features/subscription/data/payment_repository.dart';
import '../features/subscription/data/subscription_repository.dart';
import '../features/subscription/data/voucher_repository.dart';
import '../features/tags/data/tag_repository.dart';
import '../features/vehicles/data/vehicle_repository.dart';

final secureStorageProvider =
    Provider<SecureStorageService>((ref) => SecureStorageService());

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient(
    ref.watch(secureStorageProvider),
    // Read lazily (inside the callback) so the providers do not depend on each
    // other at build time.
    onSessionInvalid: (code) => ref
        .read(authControllerProvider.notifier)
        .sessionEnded(replaced: code == 'SESSION_REPLACED'),
    languageCode: () => ref.read(localeProvider).languageCode,
  );
});

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(
      ref.watch(apiClientProvider), ref.watch(secureStorageProvider));
});

final biometricServiceProvider = Provider<BiometricService>((ref) {
  return BiometricService(ref.watch(secureStorageProvider));
});

final vehicleRepositoryProvider = Provider<VehicleRepository>((ref) {
  return VehicleRepository(ref.watch(apiClientProvider));
});

final tagRepositoryProvider = Provider<TagRepository>((ref) {
  return TagRepository(ref.watch(apiClientProvider));
});

final pushNotificationServiceProvider =
    Provider<PushNotificationService>((ref) {
  return PushNotificationService(ref.watch(apiClientProvider));
});

final paymentRepositoryProvider = Provider<PaymentRepository>((ref) {
  return PaymentRepository(ref.watch(apiClientProvider));
});

final referralRepositoryProvider = Provider<ReferralRepository>((ref) {
  return ReferralRepository(ref.watch(apiClientProvider));
});

final voucherRepositoryProvider = Provider<VoucherRepository>((ref) {
  return VoucherRepository(ref.watch(apiClientProvider));
});

final subscriptionRepositoryProvider = Provider<SubscriptionRepository>((ref) {
  return SubscriptionRepository(ref.watch(apiClientProvider));
});

final notificationRepositoryProvider = Provider<NotificationRepository>((ref) {
  return NotificationRepository(ref.watch(apiClientProvider));
});

final supportRepositoryProvider = Provider<SupportRepository>((ref) {
  return SupportRepository(ref.watch(apiClientProvider));
});

final scanContactRepositoryProvider = Provider<ScanContactRepository>((ref) {
  return ScanContactRepository(ref.watch(apiClientProvider));
});

final contactRequestRepositoryProvider =
    Provider<ContactRequestRepository>((ref) {
  return ContactRequestRepository(ref.watch(apiClientProvider));
});

final profileRepositoryProvider = Provider<ProfileRepository>((ref) {
  return ProfileRepository(ref.watch(apiClientProvider));
});

final homeRepositoryProvider = Provider<HomeRepository>((ref) {
  return HomeRepository(ref.watch(apiClientProvider));
});
