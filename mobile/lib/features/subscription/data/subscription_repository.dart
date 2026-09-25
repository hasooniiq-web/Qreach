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

  Future<PublicContactModel> fetchPublicContact() async {
    final response =
        await _client.dio.get<Map<String, dynamic>>('/public-contact');
    return PublicContactModel.fromJson(response.data!);
  }

  /// Empty strings clear a field; the server rejects non-empty values with
  /// PLAN_REQUIRED (403) unless the user's plan allows showing them.
  Future<PublicContactModel> updatePublicContact({
    required String phone,
    required String instagram,
    required String extra,
  }) async {
    final response = await _client.dio.put<Map<String, dynamic>>(
      '/public-contact',
      data: {
        'phone': phone.isEmpty ? null : phone,
        'instagram': instagram.isEmpty ? null : instagram,
        'extra': extra.isEmpty ? null : extra,
      },
    );
    return PublicContactModel.fromJson(response.data!);
  }
}
