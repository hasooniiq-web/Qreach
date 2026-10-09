import '../../../core/network/api_client.dart';
import 'payment_models.dart';

class PaymentRepository {
  PaymentRepository(this._client);

  final ApiClient _client;

  Future<List<PaymentMethodModel>> methods() async {
    final response =
        await _client.dio.get<Map<String, dynamic>>('/payments/methods');
    return (response.data!['data'] as List<dynamic>)
        .map((m) => PaymentMethodModel.fromJson(m as Map<String, dynamic>))
        .toList();
  }

  /// Starts a payment. No price is sent: the server charges the plan's price.
  /// [idempotencyKey] makes a retry of the same attempt return the same payment.
  Future<StartedPayment> start({
    required String planSlug,
    required String gateway,
    required String idempotencyKey,
  }) async {
    final response =
        await _client.dio.post<Map<String, dynamic>>('/payments', data: {
      'plan': planSlug,
      'gateway': gateway,
      'idempotency_key': idempotencyKey,
    });
    return StartedPayment(
      payment: PaymentModel.fromJson(
          response.data!['payment'] as Map<String, dynamic>),
      checkoutUrl: response.data!['checkout_url'] as String?,
    );
  }

  Future<PaymentModel> fetch(String id) async {
    final response =
        await _client.dio.get<Map<String, dynamic>>('/payments/$id');
    return PaymentModel.fromJson(
        response.data!['payment'] as Map<String, dynamic>);
  }
}
