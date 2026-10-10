import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../../../core/network/api_client.dart';
import 'profile_models.dart';

class ProfileRepository {
  ProfileRepository(this._client);

  final ApiClient _client;

  Future<ProfileModel> _parse(
          Future<Response<Map<String, dynamic>>> call) async =>
      ProfileModel.fromJson((await call).data!);

  Future<ProfileModel> fetch() =>
      _parse(_client.dio.get<Map<String, dynamic>>('/profile'));

  Future<ProfileModel> updateName(String name) => _parse(
      _client.dio.put<Map<String, dynamic>>('/profile', data: {'name': name}));

  /// [currentPassword] is only needed when the profile already has one
  /// (see [ProfileModel.hasPassword]) — a first-time set from inside the
  /// app doesn't need it.
  Future<ProfileModel> updatePassword({
    String? currentPassword,
    required String password,
  }) =>
      _parse(_client.dio.put<Map<String, dynamic>>('/profile/password', data: {
        if (currentPassword != null) 'current_password': currentPassword,
        'password': password,
      }));

  /// Whether visitors see the owner's name (hidden by default).
  Future<ProfileModel> setShowName(bool value) =>
      _parse(_client.dio.put<Map<String, dynamic>>('/profile/visibility',
          data: {'show_name': value}));

  /// Whether visitors see the avatar (hidden by default).
  Future<ProfileModel> setShowAvatar(bool value) =>
      _parse(_client.dio.put<Map<String, dynamic>>('/profile/visibility',
          data: {'show_avatar': value}));

  /// The phone number shown to visitors (a Basic feature). An empty value
  /// removes it.
  Future<ProfileModel> updatePhone(
          {required String phone, required bool visible}) =>
      _parse(_client.dio.put<Map<String, dynamic>>('/profile/contact', data: {
        'phone': {'value': phone, 'visible': visible},
      }));

  /// Plus only — the server answers 403 PLAN_REQUIRED otherwise.
  Future<ProfileModel> addMethod({
    required String type,
    required String value,
    String? label,
    bool visible = false,
  }) =>
      _parse(_client.dio.post<Map<String, dynamic>>('/profile/methods', data: {
        'type': type,
        'value': value,
        if (label != null && label.isNotEmpty) 'label': label,
        'visible': visible,
      }));

  Future<ProfileModel> setMethodVisible(int id, bool visible) =>
      _parse(_client.dio.put<Map<String, dynamic>>('/profile/methods/$id',
          data: {'visible': visible}));

  Future<ProfileModel> removeMethod(int id) =>
      _parse(_client.dio.delete<Map<String, dynamic>>('/profile/methods/$id'));

  Future<ProfileModel> saveEmergency({
    required String name,
    required String? relationship,
    required String phone,
    required bool visible,
  }) =>
      _parse(_client.dio.put<Map<String, dynamic>>('/profile/emergency', data: {
        'name': name,
        if (relationship != null && relationship.isNotEmpty)
          'relationship': relationship,
        'phone': phone,
        'visible': visible,
      }));

  Future<ProfileModel> removeEmergency() =>
      _parse(_client.dio.delete<Map<String, dynamic>>('/profile/emergency'));

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
    return _parse(
        _client.dio.post<Map<String, dynamic>>('/profile/avatar', data: form));
  }

  Future<ProfileModel> removeAvatar() =>
      _parse(_client.dio.delete<Map<String, dynamic>>('/profile/avatar'));
}
