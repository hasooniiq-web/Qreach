import 'package:dio/dio.dart';
import 'package:equatable/equatable.dart';

import '../../../core/network/api_client.dart';

/// A WhatsApp number the support team answers on.
class SupportWhatsapp extends Equatable {
  const SupportWhatsapp({required this.label, required this.phone, required this.url});

  final String label;
  final String phone;
  final String url;

  factory SupportWhatsapp.fromJson(Map<String, dynamic> json) => SupportWhatsapp(
        label: json['label'] as String? ?? '',
        phone: json['phone'] as String? ?? '',
        url: json['url'] as String? ?? '',
      );

  @override
  List<Object?> get props => [label, phone, url];
}

class SupportMessageModel extends Equatable {
  const SupportMessageModel({
    required this.fromStaff,
    required this.body,
    required this.createdAt,
    this.imageUrl,
  });

  final bool fromStaff;
  final String body;
  final DateTime createdAt;

  /// A relative API path to fetch the photo with the authenticated client.
  final String? imageUrl;

  factory SupportMessageModel.fromJson(Map<String, dynamic> json) =>
      SupportMessageModel(
        fromStaff: json['sender'] == 'staff',
        body: json['body'] as String? ?? '',
        createdAt: DateTime.parse(json['created_at'] as String),
        imageUrl: json['image_url'] as String?,
      );

  @override
  List<Object?> get props => [fromStaff, body, createdAt, imageUrl];
}

class SupportTicketModel extends Equatable {
  const SupportTicketModel({
    required this.id,
    required this.category,
    required this.subject,
    required this.status,
    required this.createdAt,
    this.lastMessage,
    this.messages = const [],
  });

  final String id;

  /// general | account | vehicle | payment | bug | other
  final String category;
  final String subject;

  /// open (waiting for support) | answered | closed
  final String status;
  final DateTime createdAt;
  final String? lastMessage;
  final List<SupportMessageModel> messages;

  bool get isClosed => status == 'closed';
  bool get isAnswered => status == 'answered';

  factory SupportTicketModel.fromJson(Map<String, dynamic> json) =>
      SupportTicketModel(
        id: json['id'] as String,
        category: json['category'] as String? ?? 'general',
        subject: json['subject'] as String? ?? '',
        status: json['status'] as String? ?? 'open',
        createdAt: DateTime.parse(json['created_at'] as String),
        lastMessage: json['last_message'] as String?,
        messages: (json['messages'] as List<dynamic>? ?? const [])
            .map((e) => SupportMessageModel.fromJson(e as Map<String, dynamic>))
            .toList(),
      );

  @override
  List<Object?> get props =>
      [id, category, subject, status, createdAt, lastMessage, messages];
}

const supportCategories = [
  'general',
  'account',
  'vehicle',
  'payment',
  'bug',
  'other',
];

class SupportRepository {
  SupportRepository(this._client);

  final ApiClient _client;

  Future<List<SupportWhatsapp>> whatsapp() async {
    final response =
        await _client.dio.get<Map<String, dynamic>>('/support/info');
    return (response.data!['whatsapp'] as List<dynamic>? ?? const [])
        .map((e) => SupportWhatsapp.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<SupportTicketModel>> tickets() async {
    final response =
        await _client.dio.get<Map<String, dynamic>>('/support/tickets');
    return (response.data!['data'] as List<dynamic>)
        .map((e) => SupportTicketModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<SupportTicketModel> ticket(String id) async {
    final response =
        await _client.dio.get<Map<String, dynamic>>('/support/tickets/$id');
    return SupportTicketModel.fromJson(response.data!);
  }

  Future<SupportTicketModel> create({
    required String category,
    required String subject,
    required String body,
    String? imagePath,
  }) async {
    final response = await _client.dio.post<Map<String, dynamic>>(
      '/support/tickets',
      data: FormData.fromMap({
        'category': category,
        'subject': subject,
        'body': body,
        if (imagePath != null)
          'image':
              await MultipartFile.fromFile(imagePath, filename: 'photo.jpg'),
      }),
    );
    return SupportTicketModel.fromJson(response.data!);
  }

  Future<SupportTicketModel> reply(String id, String body,
      {String? imagePath}) async {
    final response = await _client.dio.post<Map<String, dynamic>>(
      '/support/tickets/$id/reply',
      data: FormData.fromMap({
        if (body.isNotEmpty) 'body': body,
        if (imagePath != null)
          'image':
              await MultipartFile.fromFile(imagePath, filename: 'photo.jpg'),
      }),
    );
    return SupportTicketModel.fromJson(response.data!);
  }
}
