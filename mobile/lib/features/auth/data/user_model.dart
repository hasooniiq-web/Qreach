import 'package:equatable/equatable.dart';

class UserModel extends Equatable {
  const UserModel({
    required this.id,
    required this.phone,
    this.name,
    this.email,
    required this.status,
    required this.locale,
    required this.timezone,
  });

  final String id;
  final String phone;
  final String? name;
  final String? email;
  final String status;
  final String locale;
  final String timezone;

  factory UserModel.fromJson(Map<String, dynamic> json) => UserModel(
        id: json['id'] as String,
        phone: json['phone'] as String,
        name: json['name'] as String?,
        email: json['email'] as String?,
        status: json['status'] as String,
        locale: json['locale'] as String? ?? 'ar',
        timezone: json['timezone'] as String? ?? 'Asia/Riyadh',
      );

  @override
  List<Object?> get props => [id, phone, name, email, status, locale, timezone];
}
