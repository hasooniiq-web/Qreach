import 'package:equatable/equatable.dart';

import '../../../core/network/api_client.dart';

/// One direct-contact line the owner chose to show (phone, WhatsApp...).
class ScannedContact extends Equatable {
  const ScannedContact({
    required this.label,
    required this.value,
    this.href,
  });

  final String label;
  final String value;

  /// A tel:/https: address to open, or null for a plain text line.
  final String? href;

  factory ScannedContact.fromJson(Map<String, dynamic> json) => ScannedContact(
        label: json['label'] as String? ?? '',
        value: json['value'] as String? ?? '',
        href: json['href'] as String?,
      );

  @override
  List<Object?> get props => [label, value, href];
}

/// What scanning another vehicle's QR code shows: only what the owner chose to
/// show on the public page, plus the conversation already open with it (if any).
class ScannedVehicle extends Equatable {
  const ScannedVehicle({
    required this.token,
    this.nickname,
    this.make,
    this.model,
    this.color,
    this.vehicleType,
    this.ownerName,
    this.contacts = const [],
    this.conversationId,
  });

  final String token;
  final String? nickname;
  final String? make;
  final String? model;
  final String? color;
  final String? vehicleType;
  final String? ownerName;
  final List<ScannedContact> contacts;
  final String? conversationId;

  /// The name to show: the nickname, else make + model, else null.
  String? get title {
    if (nickname?.trim().isNotEmpty == true) return nickname!.trim();
    final mm = [make, model]
        .whereType<String>()
        .where((s) => s.trim().isNotEmpty)
        .join(' ');
    return mm.isEmpty ? null : mm;
  }

  factory ScannedVehicle.fromJson(Map<String, dynamic> json) {
    final v = json['vehicle'] as Map<String, dynamic>? ?? const {};
    return ScannedVehicle(
      token: json['token'] as String,
      nickname: v['nickname'] as String?,
      make: v['make'] as String?,
      model: v['model'] as String?,
      color: v['color'] as String?,
      vehicleType: v['vehicle_type'] as String?,
      ownerName: json['owner_name'] as String?,
      contacts: (json['contacts'] as List<dynamic>? ?? const [])
          .map((e) => ScannedContact.fromJson(e as Map<String, dynamic>))
          .toList(),
      conversationId: json['conversation_id'] as String?,
    );
  }

  @override
  List<Object?> get props => [
        token,
        nickname,
        make,
        model,
        color,
        vehicleType,
        ownerName,
        contacts,
        conversationId,
      ];
}

/// The six things that can be reported (the server holds the sentences).
const scanReasons = [
  'blocking',
  'damage',
  'malfunction',
  'safety',
  'assistance',
  'lights',
];

class ScanContactRepository {
  ScanContactRepository(this._client);

  final ApiClient _client;

  /// [raw] is whatever the QR held (an address) or a token typed by hand.
  Future<ScannedVehicle> lookup(String raw) async {
    final response = await _client.dio
        .post<Map<String, dynamic>>('/scan/lookup', data: {'token': raw});
    return ScannedVehicle.fromJson(response.data!);
  }

  /// Opens the conversation (or adds to the open one); returns its id.
  Future<String> contact({required String token, required String reason}) async {
    final response = await _client.dio.post<Map<String, dynamic>>(
        '/scan/contact',
        data: {'token': token, 'reason': reason});
    return response.data!['id'] as String;
  }
}
