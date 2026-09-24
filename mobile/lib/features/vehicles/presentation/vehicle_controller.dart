import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../data/vehicle_model.dart';

class VehicleListController extends AsyncNotifier<List<VehicleModel>> {
  @override
  Future<List<VehicleModel>> build() {
    return ref.read(vehicleRepositoryProvider).list();
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
        () => ref.read(vehicleRepositoryProvider).list());
  }

  Future<VehicleModel> create(VehicleModel vehicle) async {
    final created = await ref.read(vehicleRepositoryProvider).create(vehicle);
    await refresh();
    return created;
  }

  Future<void> delete(String id) async {
    await ref.read(vehicleRepositoryProvider).delete(id);
    await refresh();
  }
}

final vehicleListControllerProvider =
    AsyncNotifierProvider<VehicleListController, List<VehicleModel>>(
  VehicleListController.new,
);

/// Fetches a single vehicle fresh (with its tags) — used by the detail
/// screen rather than relying solely on the list's cached copy, since tag
/// state can change (activation) from a screen the list doesn't know about.
final vehicleDetailProvider =
    FutureProvider.family<VehicleModel, String>((ref, id) {
  return ref.read(vehicleRepositoryProvider).show(id);
});
