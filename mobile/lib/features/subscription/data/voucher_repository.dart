import '../../../core/network/api_client.dart';

/// What a redeemed voucher switched on.
class VoucherRedemption {
  const VoucherRedemption({
    required this.planName,
    required this.days,
    required this.endsAt,
  });

  final String planName;
  final int days;
  final DateTime? endsAt;
}

class VoucherRepository {
  VoucherRepository(this._client);

  final ApiClient _client;

  /// Throws an [ApiException] whose `code` is one of VOUCHER_INVALID,
  /// VOUCHER_INVALID_FORMAT, VOUCHER_USED, VOUCHER_EXPIRED, VOUCHER_REVOKED,
  /// VOUCHER_THROTTLED or VOUCHER_LOCKED.
  Future<VoucherRedemption> redeem(String digits) async {
    final response = await _client.dio
        .post<Map<String, dynamic>>('/subscription/redeem', data: {'code': digits});
    final data = response.data!;
    final plan = data['plan'] as Map<String, dynamic>?;

    return VoucherRedemption(
      planName: (plan?['name'] as String?) ?? '',
      days: (data['days'] as num?)?.toInt() ?? 0,
      endsAt: data['current_period_ends_at'] != null
          ? DateTime.tryParse(data['current_period_ends_at'] as String)
          : null,
    );
  }
}
