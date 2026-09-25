import 'package:equatable/equatable.dart';

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
      );

  @override
  List<Object?> get props =>
      [id, visitorName, visitorPhone, message, status, vehicle, createdAt];
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
