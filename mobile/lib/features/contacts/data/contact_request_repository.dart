import '../../../core/network/api_client.dart';
import 'contact_request_model.dart';

class ContactRequestRepository {
  ContactRequestRepository(this._client);

  final ApiClient _client;

  Future<ContactRequestFeed> fetch() async {
    final response =
        await _client.dio.get<Map<String, dynamic>>('/contact-requests');
    return ContactRequestFeed.fromJson(response.data!);
  }

  Future<void> markRead(String id) async {
    await _client.dio.put('/contact-requests/$id/read');
  }

  Future<void> markResponded(String id) async {
    await _client.dio.put('/contact-requests/$id/responded');
  }
}
