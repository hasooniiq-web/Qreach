import '../../../core/network/api_client.dart';
import 'tag_model.dart';

class TagRepository {
  TagRepository(this._client);

  final ApiClient _client;

  /// Extracts the token from a scanned public URL
  /// (e.g. https://smarttag.example/t/abc123 -> abc123) or returns the raw
  /// scanned string unchanged if it isn't a URL — a tag's QR encodes the
  /// full public URL, but accepting a bare token too is harmless and more
  /// forgiving of malformed scans.
  static String extractToken(String scannedValue) {
    final uri = Uri.tryParse(scannedValue);
    if (uri != null &&
        uri.pathSegments.length >= 2 &&
        uri.pathSegments[uri.pathSegments.length - 2] == 't') {
      return uri.pathSegments.last;
    }
    return scannedValue;
  }

  Future<TagModel> activate(
      {required String token, required String vehicleId}) async {
    final response = await _client.dio.post<Map<String, dynamic>>(
      '/tags/activate',
      data: {'token': token, 'vehicle_id': vehicleId},
    );
    return TagModel.fromJson(response.data!['data'] as Map<String, dynamic>);
  }
}
