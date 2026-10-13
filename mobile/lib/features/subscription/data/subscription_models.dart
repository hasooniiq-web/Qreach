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
    this.extraContactMethods = false,
  });

  final String slug;
  final String name;
  final String? description;
  final int price;
  final String currency;
  final bool isFree;
  final int billingPeriodDays;
  final List<String> publicContacts;

  /// Plus: may add contact platforms beyond the phone number.
  final bool extraContactMethods;

  bool allows(String contactType) => publicContacts.contains(contactType);

  /// Plus is the tier that can add extra contact methods (Basic shows only
  /// name, phone and emergency contact).
  bool get showsPublicContacts => extraContactMethods;

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
      extraContactMethods: features['extra_contact_methods'] as bool? ?? false,
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
        extraContactMethods,
      ];
}

/// The one-time free trial of Plus. The server decides everything here; the app
/// only shows the offer, the running trial, or nothing.
class TrialInfo extends Equatable {
  const TrialInfo({
    this.available = false,
    this.days = 0,
    this.used = false,
    this.active = false,
    this.endsAt,
  });

  static const none = TrialInfo();

  final bool available;
  final int days;
  final bool used;
  final bool active;
  final DateTime? endsAt;

  factory TrialInfo.fromJson(Map<String, dynamic>? json) {
    if (json == null) return none;
    return TrialInfo(
      available: json['available'] as bool? ?? false,
      days: (json['days'] as num?)?.toInt() ?? 0,
      used: json['used'] as bool? ?? false,
      active: json['active'] as bool? ?? false,
      endsAt: json['ends_at'] != null
          ? DateTime.tryParse(json['ends_at'] as String)
          : null,
    );
  }

  @override
  List<Object?> get props => [available, days, used, active, endsAt];
}

class SubscriptionInfo extends Equatable {
  const SubscriptionInfo({
    required this.plan,
    required this.isDefault,
    required this.subscriptionStatus,
    required this.endsAt,
    required this.plans,
    required this.pendingUpgrade,
    this.graceEndsAt,
    this.renewalCanceled = false,
    this.canCancelRenewal = false,
    this.trial = TrialInfo.none,
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

  /// The paid period is over but the plan still works until this moment.
  final DateTime? graceEndsAt;

  /// The customer stopped the yearly renewal; the plan runs to [endsAt].
  final bool renewalCanceled;
  final bool canCancelRenewal;
  final TrialInfo trial;

  /// The free trial ran out (and nothing paid replaced it).
  bool get trialEnded => isDefault && subscriptionStatus == 'trial_ended';

  bool get inGrace => subscriptionStatus == 'grace';

  /// True when a paid plan was in force but has since lapsed.
  bool get hasLapsed =>
      isDefault &&
      subscriptionStatus != null &&
      subscriptionStatus != 'active' &&
      subscriptionStatus != 'trial_ended';

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
      graceEndsAt: sub?['grace_ends_at'] != null
          ? DateTime.tryParse(sub!['grace_ends_at'] as String)
          : null,
      renewalCanceled: sub?['renewal_canceled'] as bool? ?? false,
      canCancelRenewal: sub?['can_cancel_renewal'] as bool? ?? false,
      trial: TrialInfo.fromJson(json['trial'] as Map<String, dynamic>?),
    );
  }

  @override
  List<Object?> get props => [
        plan,
        isDefault,
        subscriptionStatus,
        endsAt,
        plans,
        pendingUpgrade,
        graceEndsAt,
        renewalCanceled,
        canCancelRenewal,
        trial,
      ];
}
