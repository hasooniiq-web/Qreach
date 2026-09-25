import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../../../core/network/api_client.dart';
import 'profile_models.dart';

class ProfileRepository {
  ProfileRepository(this._client);

  final ApiClient _client;

  Future<ProfileModel> fetch() async {
    final response = await _client.dio.get<Map<String, dynamic>>('/profile');
    return ProfileModel.fromJson(response.data!);
  }

  Future<ProfileModel> updateName(String name) async {
    final response = await _client.dio
        .put<Map<String, dynamic>>('/profile', data: {'name': name});
    return ProfileModel.fromJson(response.data!);
  }

  /// Saves all three details at once. An empty value clears that detail;
  /// `visible` defaults to hidden. The server rejects `visible: true`
  /// (PLAN_REQUIRED) unless the plan allows showing it.
  Future<ProfileModel> updateContact({
    required String phone,
    required bool phoneVisible,
    required String instagram,
    required bool instagramVisible,
    required String extra,
    required bool extraVisible,
  }) async {
    final response = await _client.dio.put<Map<String, dynamic>>(
      '/profile/contact',
      data: {
        'phone': {'value': phone, 'visible': phoneVisible},
        'instagram': {'value': instagram, 'visible': instagramVisible},
        'extra': {'value': extra, 'visible': extraVisible},
      },
    );
    return ProfileModel.fromJson(response.data!);
  }

  /// The account photo, or null when none is set.
  Future<Uint8List?> fetchAvatar() async {
    try {
      final response = await _client.dio.get<List<int>>('/profile/avatar',
          options: Options(responseType: ResponseType.bytes));
      return Uint8List.fromList(response.data!);
    } on DioException catch (e) {
      if (e.response?.statusCode == 404) return null;
      rethrow;
    }
  }

  Future<ProfileModel> uploadAvatar(String filePath) async {
    final form = FormData.fromMap({
      'avatar': await MultipartFile.fromFile(filePath, filename: 'avatar.jpg'),
    });
    final response = await _client.dio
        .post<Map<String, dynamic>>('/profile/avatar', data: form);
    return ProfileModel.fromJson(response.data!);
  }

  Future<ProfileModel> removeAvatar() async {
    final response =
        await _client.dio.delete<Map<String, dynamic>>('/profile/avatar');
    return ProfileModel.fromJson(response.data!);
  }
}
