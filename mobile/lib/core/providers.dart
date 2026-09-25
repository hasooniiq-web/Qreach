import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'network/api_client.dart';
import 'notifications/push_notification_service.dart';
import 'storage/secure_storage_service.dart';
import '../features/auth/data/auth_repository.dart';
import '../features/tags/data/tag_repository.dart';
import '../features/vehicles/data/vehicle_repository.dart';

final secureStorageProvider =
    Provider<SecureStorageService>((ref) => SecureStorageService());

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient(ref.watch(secureStorageProvider));
});

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(
      ref.watch(apiClientProvider), ref.watch(secureStorageProvider));
});

final vehicleRepositoryProvider = Provider<VehicleRepository>((ref) {
  return VehicleRepository(ref.watch(apiClientProvider));
});

final tagRepositoryProvider = Provider<TagRepository>((ref) {
  return TagRepository(ref.watch(apiClientProvider));
});

final pushNotificationServiceProvider = Provider<PushNotificationService>((ref) {
  return PushNotificationService(ref.watch(apiClientProvider));
});
