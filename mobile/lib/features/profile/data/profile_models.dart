import 'package:equatable/equatable.dart';

/// One stored contact detail and whether visitors may see it.
class ContactField extends Equatable {
  const ContactField({
    this.value,
    this.visible = false,
    this.canShow = false,
  });

  final String? value;

  /// Whether the owner switched this on. Defaults to hidden.
  final bool visible;

  /// Whether the current plan allows showing this detail at all (Pro).
  final bool canShow;

  bool get hasValue => value != null && value!.isNotEmpty;

  factory ContactField.fromJson(Map<String, dynamic> json) => ContactField(
        value: json['value'] as String?,
        visible: json['visible'] as bool? ?? false,
        canShow: json['can_show'] as bool? ?? false,
      );

  @override
  List<Object?> get props => [value, visible, canShow];
}

class ProfileModel extends Equatable {
  const ProfileModel({
    required this.name,
    required this.phone,
    required this.plan,
    required this.phoneField,
    required this.instagram,
    required this.extra,
  });

  final String? name;

  /// The login number — read-only, never shown to visitors by itself.
  final String phone;
  final String plan;
  final ContactField phoneField;
  final ContactField instagram;
  final ContactField extra;

  /// True if any detail is currently switched on for visitors.
  bool get sharesAnything =>
      (phoneField.visible && phoneField.canShow) ||
      (instagram.visible && instagram.canShow) ||
      (extra.visible && extra.canShow);

  factory ProfileModel.fromJson(Map<String, dynamic> json) {
    final contact = json['contact'] as Map<String, dynamic>? ?? const {};
    ContactField field(String key) => ContactField.fromJson(
        contact[key] as Map<String, dynamic>? ?? const {});

    return ProfileModel(
      name: json['name'] as String?,
      phone: json['phone'] as String? ?? '',
      plan: json['plan'] as String? ?? 'basic',
      phoneField: field('phone'),
      instagram: field('instagram'),
      extra: field('extra'),
    );
  }

  @override
  List<Object?> get props => [name, phone, plan, phoneField, instagram, extra];
}
