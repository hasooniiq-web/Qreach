import '../../../core/network/api_client.dart';
import 'home_models.dart';

class HomeRepository {
  HomeRepository(this._client);

  final ApiClient _client;

  Future<HomeData> fetch() async {
    final response = await _client.dio.get<Map<String, dynamic>>('/home');
    return HomeData.fromJson(response.data!);
  }
}
