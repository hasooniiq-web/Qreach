import 'package:equatable/equatable.dart';

class TagModel extends Equatable {
  const TagModel({
    required this.token,
    required this.serialNumber,
    required this.type,
    required this.status,
    this.activatedAt,
    this.assignedAt,
    required this.publicUrl,
  });

  final String token;
  final String serialNumber;
  final String type;
  final String status;
  final DateTime? activatedAt;
  final DateTime? assignedAt;
  final String publicUrl;

  factory TagModel.fromJson(Map<String, dynamic> json) => TagModel(
        token: json['token'] as String,
        serialNumber: json['serial_number'] as String,
        type: json['type'] as String,
        status: json['status'] as String,
        activatedAt: json['activated_at'] != null
            ? DateTime.tryParse(json['activated_at'] as String)
            : null,
        assignedAt: json['assigned_at'] != null
            ? DateTime.tryParse(json['assigned_at'] as String)
            : null,
        publicUrl: json['public_url'] as String,
      );

  @override
  List<Object?> get props =>
      [token, serialNumber, type, status, activatedAt, assignedAt, publicUrl];
}
