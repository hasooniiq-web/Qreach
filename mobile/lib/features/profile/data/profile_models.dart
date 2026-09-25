import 'package:equatable/equatable.dart';

/// One stored detail and whether visitors may see it.
class ContactField extends Equatable {
  const ContactField({
    this.value,
    this.visible = false,
    this.canShow = false,
  });

  final String? value;

  /// Whether the owner switched this on. Defaults to hidden.
  final bool visible;

  /// Whether the current plan allows showing this detail at all.
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

/// An extra contact platform the owner added (Plus): Instagram, WhatsApp...
class ContactMethodModel extends Equatable {
  const ContactMethodModel({
    required this.id,
    required this.type,
    required this.label,
    required this.value,
    required this.visible,
    required this.canShow,
  });

  final int id;
  final String type;
  final String label;
  final String value;
  final bool visible;
  final bool canShow;

  factory ContactMethodModel.fromJson(Map<String, dynamic> json) =>
      ContactMethodModel(
        id: (json['id'] as num).toInt(),
        type: json['type'] as String,
        label: json['label'] as String? ?? json['type'] as String,
        value: json['value'] as String? ?? '',
        visible: json['visible'] as bool? ?? false,
        canShow: json['can_show'] as bool? ?? false,
      );

  @override
  List<Object?> get props => [id, type, label, value, visible, canShow];
}

class PlatformInfo extends Equatable {
  const PlatformInfo(
      {required this.type, required this.label, required this.hint});

  final String type;
  final String label;
  final String hint;

  factory PlatformInfo.fromJson(Map<String, dynamic> json) => PlatformInfo(
        type: json['type'] as String,
        label: json['label'] as String? ?? json['type'] as String,
        hint: json['hint'] as String? ?? '',
      );

  @override
  List<Object?> get props => [type, label, hint];
}

class EmergencyModel extends Equatable {
  const EmergencyModel({
    required this.name,
    required this.relationship,
    required this.phone,
    required this.visible,
    required this.canShow,
  });

  final String name;
  final String? relationship;
  final String phone;
  final bool visible;
  final bool canShow;

  factory EmergencyModel.fromJson(Map<String, dynamic> json) => EmergencyModel(
        name: json['name'] as String? ?? '',
        relationship: json['relationship'] as String?,
        phone: json['phone'] as String? ?? '',
        visible: json['visible'] as bool? ?? false,
        canShow: json['can_show'] as bool? ?? true,
      );

  @override
  List<Object?> get props => [name, relationship, phone, visible, canShow];
}

class ProfileModel extends Equatable {
  const ProfileModel({
    required this.name,
    required this.phone,
    required this.plan,
    required this.phoneField,
    this.showName = false,
    this.canAddMethods = false,
    this.methods = const [],
    this.platforms = const [],
    this.emergency,
    this.hasAvatar = false,
    this.avatarVersion,
  });

  final String? name;

  /// The login number — read-only, never shown to visitors by itself.
  final String phone;
  final String plan;

  /// The number the owner may show to visitors (Basic and up).
  final ContactField phoneField;

  /// Whether visitors see the owner's name. Off by default.
  final bool showName;

  /// Plus: may add extra contact platforms.
  final bool canAddMethods;
  final List<ContactMethodModel> methods;
  final List<PlatformInfo> platforms;
  final EmergencyModel? emergency;
  final bool hasAvatar;

  /// Changes whenever the account is updated; lets the photo be re-fetched.
  final int? avatarVersion;

  /// True if anything is currently switched on for visitors.
  bool get sharesAnything =>
      showName ||
      (phoneField.visible && phoneField.canShow) ||
      (emergency?.visible == true && emergency?.canShow == true) ||
      methods.any((m) => m.visible && m.canShow);

  factory ProfileModel.fromJson(Map<String, dynamic> json) {
    final contact = json['contact'] as Map<String, dynamic>? ?? const {};

    return ProfileModel(
      name: json['name'] as String?,
      phone: json['phone'] as String? ?? '',
      plan: json['plan'] as String? ?? 'basic',
      phoneField: ContactField.fromJson(
          contact['phone'] as Map<String, dynamic>? ?? const {}),
      showName: json['show_name'] as bool? ?? false,
      canAddMethods: json['can_add_methods'] as bool? ?? false,
      methods: (json['methods'] as List<dynamic>? ?? const [])
          .map((e) => ContactMethodModel.fromJson(e as Map<String, dynamic>))
          .toList(),
      platforms: (json['platforms'] as List<dynamic>? ?? const [])
          .map((e) => PlatformInfo.fromJson(e as Map<String, dynamic>))
          .toList(),
      emergency: json['emergency'] == null
          ? null
          : EmergencyModel.fromJson(json['emergency'] as Map<String, dynamic>),
      hasAvatar: json['has_avatar'] as bool? ?? false,
      avatarVersion: (json['avatar_version'] as num?)?.toInt(),
    );
  }

  @override
  List<Object?> get props => [
        name,
        phone,
        plan,
        phoneField,
        showName,
        canAddMethods,
        methods,
        platforms,
        emergency,
        hasAvatar,
        avatarVersion,
      ];
}
