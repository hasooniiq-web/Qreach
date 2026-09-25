import 'package:equatable/equatable.dart';

class HomeConversation extends Equatable {
  const HomeConversation({
    required this.id,
    required this.visitorName,
    required this.vehicle,
    required this.preview,
    required this.fromOwner,
    required this.unread,
    required this.at,
  });

  final String id;
  final String? visitorName;
  final String? vehicle;
  final String preview;

  /// True when the last message in the conversation is the owner's reply.
  final bool fromOwner;
  final bool unread;
  final DateTime at;

  factory HomeConversation.fromJson(Map<String, dynamic> json) =>
      HomeConversation(
        id: json['id'] as String,
        visitorName: json['visitor_name'] as String?,
        vehicle: json['vehicle'] as String?,
        preview: json['preview'] as String? ?? '',
        fromOwner: json['from_owner'] as bool? ?? false,
        unread: json['unread'] as bool? ?? false,
        at: DateTime.parse(json['at'] as String),
      );

  @override
  List<Object?> get props =>
      [id, visitorName, vehicle, preview, fromOwner, unread, at];
}

class ActivityItem extends Equatable {
  const ActivityItem({required this.text, required this.at});

  final String text;
  final DateTime at;

  factory ActivityItem.fromJson(Map<String, dynamic> json) => ActivityItem(
        text: json['text'] as String? ?? '',
        at: DateTime.parse(json['at'] as String),
      );

  @override
  List<Object?> get props => [text, at];
}

/// Everything the home screen shows under the vehicle carousel: the latest
/// three conversations and four equal activity areas.
class HomeData extends Equatable {
  const HomeData({
    this.conversations = const [],
    this.latest = const [],
    this.communication = const [],
    this.account = const [],
    this.reports = const [],
    this.unreadMessages = 0,
  });

  final List<HomeConversation> conversations;
  final List<ActivityItem> latest;
  final List<ActivityItem> communication;
  final List<ActivityItem> account;
  final List<ActivityItem> reports;
  final int unreadMessages;

  static List<ActivityItem> _items(dynamic list) =>
      (list as List<dynamic>? ?? const [])
          .map((e) => ActivityItem.fromJson(e as Map<String, dynamic>))
          .toList();

  factory HomeData.fromJson(Map<String, dynamic> json) {
    final a = json['activities'] as Map<String, dynamic>? ?? const {};

    return HomeData(
      conversations: (json['conversations'] as List<dynamic>? ?? const [])
          .map((e) => HomeConversation.fromJson(e as Map<String, dynamic>))
          .toList(),
      latest: _items(a['latest']),
      communication: _items(a['communication']),
      account: _items(a['account']),
      reports: _items(a['reports']),
      unreadMessages: (json['unread_messages'] as num?)?.toInt() ?? 0,
    );
  }

  @override
  List<Object?> get props =>
      [conversations, latest, communication, account, reports, unreadMessages];
}
