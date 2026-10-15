import 'dart:typed_data';

import 'package:dio/dio.dart';

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

  Future<ContactRequestModel> fetchOne(String id) async {
    final response =
        await _client.dio.get<Map<String, dynamic>>('/contact-requests/$id');
    return ContactRequestModel.fromJson(response.data!);
  }

  /// Sends the owner's reply: text, a photo, or both (at least one).
  Future<ContactRequestModel> reply(String id, String body,
      {String? imagePath}) async {
    final response = await _client.dio
        .post<Map<String, dynamic>>('/contact-requests/$id/reply',
            data: imagePath == null
                ? {'body': body}
                : FormData.fromMap({
                    if (body.isNotEmpty) 'body': body,
                    'image': await MultipartFile.fromFile(imagePath,
                        filename: 'photo.jpg'),
                  }));
    return ContactRequestModel.fromJson(response.data!);
  }

  /// Either side reports the other; it goes to the Qreach team, never to them.
  Future<void> report(String id, String reason, {String? details}) async {
    await _client.dio.post('/contact-requests/$id/report', data: {
      'reason': reason,
      if (details != null && details.isNotEmpty) 'details': details,
    });
  }

  /// The person who started it from the app shows or hides their name.
  Future<ContactRequestModel> setIdentityVisible(String id, bool visible) async {
    final response = await _client.dio.put<Map<String, dynamic>>(
        '/contact-requests/$id/identity',
        data: {'visible': visible});
    return ContactRequestModel.fromJson(response.data!);
  }

  Future<void> markRead(String id) async {
    await _client.dio.put('/contact-requests/$id/read');
  }

  Future<void> markResponded(String id) async {
    await _client.dio.put('/contact-requests/$id/responded');
  }

  /// A photo a visitor attached in the thread, fetched with the same
  /// authenticated client as everything else — never a bare network image.
  Future<Uint8List> fetchMessageImage(String path) async {
    final response = await _client.dio.get<List<int>>(path,
        options: Options(responseType: ResponseType.bytes));
    return Uint8List.fromList(response.data!);
  }
}
