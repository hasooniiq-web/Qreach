import 'package:equatable/equatable.dart';

/// Contact-detail types a plan may show publicly (mirrors the backend's
/// `features.public_contacts`). The server decides — the app only reflects it.
abstract final class ContactType {
  static const phone = 'phone';
  static const instagram = 'instagram';
  static const other = 'other';
}

class PlanModel extends Equatable {
  const PlanModel({
    required this.slug,
    required this.name,
    required this.description,
    required this.price,
    required this.currency,
    required this.isFree,
    required this.billingPeriodDays,
    required this.publicContacts,
  });

  final String slug;
  final String name;
  final String? description;
  final int price;
  final String currency;
  final bool isFree;
  final int billingPeriodDays;
  final List<String> publicContacts;

  bool allows(String contactType) => publicContacts.contains(contactType);

  /// A plan that shows any contact detail publicly is the "Pro" tier.
  bool get showsPublicContacts => publicContacts.isNotEmpty;

  factory PlanModel.fromJson(Map<String, dynamic> json) {
    final features = json['features'] as Map<String, dynamic>? ?? const {};
    return PlanModel(
      slug: json['slug'] as String,
      name: json['name'] as String,
      description: json['description'] as String?,
      price: (json['price'] as num).toInt(),
      currency: json['currency'] as String? ?? 'IQD',
      isFree: json['is_free'] as bool? ?? false,
      billingPeriodDays: (json['billing_period_days'] as num?)?.toInt() ?? 365,
      publicContacts:
          (features['public_contacts'] as List<dynamic>? ?? const [])
              .map((e) => e as String)
              .toList(),
    );
  }

  @override
  List<Object?> get props => [
        slug,
        name,
        description,
        price,
        currency,
        isFree,
        billingPeriodDays,
        publicContacts,
      ];
}

class SubscriptionInfo extends Equatable {
  const SubscriptionInfo({
    required this.plan,
    required this.isDefault,
    required this.subscriptionStatus,
    required this.endsAt,
    required this.plans,
    required this.pendingUpgrade,
  });

  /// The plan currently in force (Basic when there is no active subscription).
  final PlanModel plan;
  final bool isDefault;

  /// Status of the latest subscription record, even if it has lapsed
  /// ("active", "expired", "canceled", ...); null if the user never had one.
  final String? subscriptionStatus;
  final DateTime? endsAt;
  final List<PlanModel> plans;
  final bool pendingUpgrade;

  /// True when a paid plan was in force but has since lapsed.
  bool get hasLapsed =>
      isDefault && subscriptionStatus != null && subscriptionStatus != 'active';

  factory SubscriptionInfo.fromJson(Map<String, dynamic> json) {
    final sub = json['subscription'] as Map<String, dynamic>?;
    return SubscriptionInfo(
      plan: PlanModel.fromJson(json['plan'] as Map<String, dynamic>),
      isDefault: json['source'] == 'default',
      subscriptionStatus: sub?['status'] as String?,
      endsAt: sub?['current_period_ends_at'] != null
          ? DateTime.tryParse(sub!['current_period_ends_at'] as String)
          : null,
      plans: (json['plans'] as List<dynamic>)
          .map((p) => PlanModel.fromJson(p as Map<String, dynamic>))
          .toList(),
      pendingUpgrade: json['pending_upgrade'] as bool? ?? false,
    );
  }

  @override
  List<Object?> get props =>
      [plan, isDefault, subscriptionStatus, endsAt, plans, pendingUpgrade];
}

class PublicContactModel extends Equatable {
  const PublicContactModel({
    required this.editable,
    this.phone,
    this.instagram,
    this.extra,
  });

  final bool editable;
  final String? phone;
  final String? instagram;
  final String? extra;

  factory PublicContactModel.fromJson(Map<String, dynamic> json) =>
      PublicContactModel(
        editable: json['editable'] as bool? ?? false,
        phone: json['phone'] as String?,
        instagram: json['instagram'] as String?,
        extra: json['extra'] as String?,
      );

  @override
  List<Object?> get props => [editable, phone, instagram, extra];
}
