import '../../../core/network/api_client.dart';
import 'notification_model.dart';

class NotificationRepository {
  NotificationRepository(this._client);

  final ApiClient _client;

  Future<NotificationFeed> fetch() async {
    final response =
        await _client.dio.get<Map<String, dynamic>>('/notifications');
    return NotificationFeed.fromJson(response.data!);
  }

  Future<void> markRead(String id) async {
    await _client.dio.put('/notifications/$id/read');
  }

  Future<void> markAllRead() async {
    await _client.dio.put('/notifications/read-all');
  }
}
