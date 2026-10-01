import 'package:equatable/equatable.dart';

class ThreadMessage extends Equatable {
  const ThreadMessage({
    required this.fromOwner,
    required this.body,
    required this.createdAt,
    this.imageUrl,
  });

  final bool fromOwner;
  final String body;
  final DateTime createdAt;

  /// A relative API path (e.g. "/contact-requests/{uuid}/messages/{id}/image")
  /// to fetch the photo the visitor attached, or null if there isn't one.
  final String? imageUrl;

  factory ThreadMessage.fromJson(Map<String, dynamic> json) => ThreadMessage(
        fromOwner: json['sender'] == 'owner',
        body: json['body'] as String? ?? '',
        createdAt: DateTime.parse(json['created_at'] as String),
        imageUrl: json['image_url'] as String?,
      );

  @override
  List<Object?> get props => [fromOwner, body, createdAt, imageUrl];
}

/// A message a visitor sent after scanning one of the owner's vehicle tags.
class ContactRequestModel extends Equatable {
  const ContactRequestModel({
    required this.id,
    required this.visitorName,
    required this.visitorPhone,
    required this.message,
    required this.status,
    required this.vehicle,
    required this.createdAt,
    this.expiresAt,
    this.messages = const [],
  });

  final String id;
  final String? visitorName;

  /// Only present if the visitor chose to leave a number to be called back on.
  final String? visitorPhone;
  final String message;

  /// new | read | responded | closed
  final String status;
  final String? vehicle;
  final DateTime createdAt;

  /// The conversation is deleted automatically at this time (15 days after
  /// the last message).
  final DateTime? expiresAt;

  /// Follow-up messages after the first one (`message`); only loaded when the
  /// conversation is opened.
  final List<ThreadMessage> messages;

  bool get isNew => status == 'new';
  bool get isHandled => status == 'responded' || status == 'closed';

  ContactRequestModel copyWith({String? status}) => ContactRequestModel(
        id: id,
        visitorName: visitorName,
        visitorPhone: visitorPhone,
        message: message,
        status: status ?? this.status,
        vehicle: vehicle,
        createdAt: createdAt,
        expiresAt: expiresAt,
        messages: messages,
      );

  factory ContactRequestModel.fromJson(Map<String, dynamic> json) =>
      ContactRequestModel(
        id: json['id'] as String,
        visitorName: json['visitor_name'] as String?,
        visitorPhone: json['visitor_phone'] as String?,
        message: json['message'] as String? ?? '',
        status: json['status'] as String? ?? 'new',
        vehicle: json['vehicle'] as String?,
        createdAt: DateTime.parse(json['created_at'] as String),
        expiresAt: json['expires_at'] != null
            ? DateTime.tryParse(json['expires_at'] as String)
            : null,
        messages: (json['messages'] as List<dynamic>? ?? const [])
            .map((e) => ThreadMessage.fromJson(e as Map<String, dynamic>))
            .toList(),
      );

  @override
  List<Object?> get props => [
        id,
        visitorName,
        visitorPhone,
        message,
        status,
        vehicle,
        createdAt,
        expiresAt,
        messages
      ];
}

class ContactRequestFeed extends Equatable {
  const ContactRequestFeed({required this.items, required this.unreadCount});

  final List<ContactRequestModel> items;
  final int unreadCount;

  factory ContactRequestFeed.fromJson(Map<String, dynamic> json) =>
      ContactRequestFeed(
        items: (json['data'] as List<dynamic>)
            .map((e) => ContactRequestModel.fromJson(e as Map<String, dynamic>))
            .toList(),
        unreadCount: (json['unread_count'] as num?)?.toInt() ?? 0,
      );

  @override
  List<Object?> get props => [items, unreadCount];
}
