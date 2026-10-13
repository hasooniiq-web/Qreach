import 'package:equatable/equatable.dart';

import '../../../core/network/api_client.dart';

/// "Invite a friend": the customer's own code and what it has earned. The server
/// decides every rule (who may enter a code, the reward, the cap); the app shows.
class ReferralInfo extends Equatable {
  const ReferralInfo({
    required this.code,
    required this.rewardDays,
    required this.enabled,
    required this.canApply,
    required this.referred,
    required this.invited,
    required this.rewarded,
    required this.earnedDays,
    this.maxInvites = 2,
    this.remainingInvites = 2,
  });

  /// Grouped for reading aloud, e.g. `K7M2-P9QX`.
  final String code;
  final int rewardDays;
  final bool enabled;

  /// This customer may still enter a friend's code.
  final bool canApply;
  final bool referred;
  final int invited;
  final int rewarded;
  final int earnedDays;

  /// How many friends one account may invite, and how many of those are still free.
  final int maxInvites;
  final int remainingInvites;

  factory ReferralInfo.fromJson(Map<String, dynamic> json) => ReferralInfo(
        code: json['code'] as String? ?? '',
        rewardDays: (json['reward_days'] as num?)?.toInt() ?? 0,
        enabled: json['enabled'] as bool? ?? false,
        canApply: json['can_apply'] as bool? ?? false,
        referred: json['referred'] as bool? ?? false,
        invited: (json['invited'] as num?)?.toInt() ?? 0,
        rewarded: (json['rewarded'] as num?)?.toInt() ?? 0,
        earnedDays: (json['earned_days'] as num?)?.toInt() ?? 0,
        maxInvites: (json['max_invites'] as num?)?.toInt() ?? 2,
        remainingInvites: (json['remaining_invites'] as num?)?.toInt() ?? 2,
      );

  @override
  List<Object?> get props => [
        code,
        rewardDays,
        enabled,
        canApply,
        referred,
        invited,
        rewarded,
        earnedDays,
        maxInvites,
        remainingInvites,
      ];
}

class ReferralRepository {
  ReferralRepository(this._client);

  final ApiClient _client;

  Future<ReferralInfo> fetch() async {
    final response = await _client.dio.get<Map<String, dynamic>>('/referral');
    return ReferralInfo.fromJson(response.data!);
  }

  /// Throws an [ApiException] whose `code` is REFERRAL_INVALID, REFERRAL_SELF,
  /// REFERRAL_ALREADY, REFERRAL_NOT_NEW or REFERRAL_UNAVAILABLE.
  Future<ReferralInfo> apply(String code) async {
    final response = await _client.dio
        .post<Map<String, dynamic>>('/referral/apply', data: {'code': code});
    return ReferralInfo.fromJson(response.data!);
  }
}
