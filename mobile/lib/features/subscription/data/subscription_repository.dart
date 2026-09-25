import '../../../core/network/api_client.dart';
import 'subscription_models.dart';

class SubscriptionRepository {
  SubscriptionRepository(this._client);

  final ApiClient _client;

  Future<SubscriptionInfo> fetch() async {
    final response =
        await _client.dio.get<Map<String, dynamic>>('/subscription');
    return SubscriptionInfo.fromJson(response.data!);
  }

  Future<void> requestUpgrade(String planSlug) async {
    await _client.dio
        .post('/subscription/upgrade-requests', data: {'plan': planSlug});
  }
}
