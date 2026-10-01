import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/providers.dart';
import 'package:qreach_smarttag/features/auth/data/auth_repository.dart';
import 'package:qreach_smarttag/features/auth/data/user_model.dart';
import 'package:qreach_smarttag/features/auth/presentation/auth_controller.dart';
import 'package:qreach_smarttag/features/vehicles/data/vehicle_model.dart';
import 'package:qreach_smarttag/features/vehicles/data/vehicle_repository.dart';
import 'package:qreach_smarttag/features/vehicles/presentation/vehicle_controller.dart';

/// Returns a fresh list each call, tagged with the call count, so a test can
/// tell "still showing the first fetch" apart from "fetched again".
class _CountingVehicles implements VehicleRepository {
  int calls = 0;

  @override
  Future<List<VehicleModel>> list() async {
    calls++;
    return [VehicleModel(id: 'v$calls', plateNumber: 'fetch-$calls')];
  }

  @override
  Future<VehicleModel> show(String id) async => (await list()).first;
  @override
  Future<VehicleModel> create(VehicleModel vehicle) async => vehicle;
  @override
  Future<VehicleModel> update(String id, VehicleModel vehicle) async => vehicle;
  @override
  Future<void> delete(String id) async {}
  @override
  Future<void> removeTag(String vehicleId) async {}
}

class _StubAuth implements AuthRepository {
  @override
  Future<void> logout() async {}
  @override
  Future<void> logoutAllDevices() async {}
  @override
  Future<bool> hasStoredSession() async => false;
  @override
  Future<void> register(
      {required String name,
      required String phone,
      required String password}) async {}
  @override
  Future<UserModel> verifyRegister(
          {required String phone, required String code, String? deviceName}) =>
      throw UnimplementedError();
  @override
  Future<UserModel> login(
          {required String phone,
          required String password,
          String? deviceName}) =>
      throw UnimplementedError();
  @override
  Future<void> requestPasswordOtp(String phone) async {}
  @override
  Future<UserModel> setPassword(
          {required String phone,
          required String code,
          required String password,
          String? deviceName}) =>
      throw UnimplementedError();
}

void main() {
  test(
      'logout invalidates the vehicle list so a different account does not '
      'see the previous one\'s cars until a manual refresh', () async {
    final vehicles = _CountingVehicles();
    final container = ProviderContainer(overrides: [
      vehicleRepositoryProvider.overrideWithValue(vehicles),
      authRepositoryProvider.overrideWithValue(_StubAuth()),
    ]);
    addTearDown(container.dispose);

    // Simulates a screen watching the list — without an active listener,
    // invalidate() wouldn't eagerly refetch, same as Riverpod's real rule.
    container.listen(vehicleListControllerProvider, (_, __) {});

    final first = await container.read(vehicleListControllerProvider.future);
    expect(first.single.plateNumber, 'fetch-1');
    expect(vehicles.calls, 1);

    await container.read(authControllerProvider.notifier).logout();

    final second = await container.read(vehicleListControllerProvider.future);
    expect(vehicles.calls, 2,
        reason:
            'the bug: logging out must invalidate the cached list, not leave '
            'the previous account\'s vehicles showing until a manual refresh');
    expect(second.single.plateNumber, 'fetch-2');
  });
}
