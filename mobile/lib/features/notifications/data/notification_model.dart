import 'package:equatable/equatable.dart';

class NotificationModel extends Equatable {
  const NotificationModel({
    required this.id,
    required this.type,
    required this.actionType,
    required this.title,
    required this.message,
    required this.visitorName,
    required this.vehicle,
    required this.read,
    required this.createdAt,
  });

  final String id;

  /// `tag_action` (a visitor acted on a scanned tag) or `contact_request`.
  final String type;

  /// For tag actions: vehicle_block, lights_on, important_message, emergency.
  final String? actionType;
  final String title;
  final String? message;
  final String? visitorName;

  /// The vehicle's nickname or plate, as named by the server.
  final String? vehicle;
  final bool read;
  final DateTime createdAt;

  bool get isEmergency => actionType == 'emergency';

  NotificationModel copyWith({bool? read}) => NotificationModel(
        id: id,
        type: type,
        actionType: actionType,
        title: title,
        message: message,
        visitorName: visitorName,
        vehicle: vehicle,
        read: read ?? this.read,
        createdAt: createdAt,
      );

  factory NotificationModel.fromJson(Map<String, dynamic> json) =>
      NotificationModel(
        id: json['id'] as String,
        type: json['type'] as String? ?? 'other',
        actionType: json['action_type'] as String?,
        title: json['title'] as String? ?? '',
        message: json['message'] as String?,
        visitorName: json['visitor_name'] as String?,
        vehicle: json['vehicle'] as String?,
        read: json['read'] as bool? ?? false,
        createdAt: DateTime.parse(json['created_at'] as String),
      );

  @override
  List<Object?> get props => [
        id,
        type,
        actionType,
        title,
        message,
        visitorName,
        vehicle,
        read,
        createdAt
      ];
}

class NotificationFeed extends Equatable {
  const NotificationFeed({required this.items, required this.unreadCount});

  final List<NotificationModel> items;
  final int unreadCount;

  factory NotificationFeed.fromJson(Map<String, dynamic> json) =>
      NotificationFeed(
        items: (json['data'] as List<dynamic>)
            .map((e) => NotificationModel.fromJson(e as Map<String, dynamic>))
            .toList(),
        unreadCount: (json['unread_count'] as num?)?.toInt() ?? 0,
      );

  @override
  List<Object?> get props => [items, unreadCount];
}
