import '../../../core/network/api_client.dart';
import 'vehicle_model.dart';

class VehicleRepository {
  VehicleRepository(this._client);

  final ApiClient _client;

  Future<List<VehicleModel>> list() async {
    final response = await _client.dio.get<Map<String, dynamic>>('/vehicles');
    final data = response.data!['data'] as List<dynamic>;
    return data
        .map((v) => VehicleModel.fromJson(v as Map<String, dynamic>))
        .toList();
  }

  Future<VehicleModel> show(String id) async {
    final response =
        await _client.dio.get<Map<String, dynamic>>('/vehicles/$id');
    return VehicleModel.fromJson(
        response.data!['data'] as Map<String, dynamic>);
  }

  Future<VehicleModel> create(VehicleModel vehicle) async {
    final response = await _client.dio
        .post<Map<String, dynamic>>('/vehicles', data: vehicle.toCreateJson());
    return VehicleModel.fromJson(
        response.data!['data'] as Map<String, dynamic>);
  }

  Future<VehicleModel> update(String id, VehicleModel vehicle) async {
    final response = await _client.dio.put<Map<String, dynamic>>(
        '/vehicles/$id',
        data: vehicle.toUpdateJson());
    return VehicleModel.fromJson(
        response.data!['data'] as Map<String, dynamic>);
  }

  /// Pauses (`inactive`) or resumes (`active`) a vehicle; the server decides what
  /// that means for its public code.
  Future<void> setStatus(String id, {required bool active}) async {
    await _client.dio
        .put('/vehicles/$id', data: {'status': active ? 'active' : 'inactive'});
  }

  Future<void> delete(String id) async {
    await _client.dio.delete('/vehicles/$id');
  }

  /// Removes the vehicle's code so a new one can be linked (one code per vehicle).
  Future<void> removeTag(String vehicleId) async {
    await _client.dio.delete('/vehicles/$vehicleId/tag');
  }

  /// Links the code the owner removed last back to this vehicle.
  Future<void> relinkTag(String vehicleId) async {
    await _client.dio.post('/vehicles/$vehicleId/tag/relink');
  }

  /// Switches the printed QR and / or the NFC chip of the vehicle's code on or off.
  Future<void> setChannels(String vehicleId,
      {required bool qr, required bool nfc}) async {
    await _client.dio.put('/vehicles/$vehicleId/tag/channels',
        data: {'qr_enabled': qr, 'nfc_enabled': nfc});
  }
}
