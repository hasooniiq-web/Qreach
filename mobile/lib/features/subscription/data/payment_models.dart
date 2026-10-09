import 'package:equatable/equatable.dart';

/// A way to pay that the server currently offers (enabled and configured by an admin).
class PaymentMethodModel extends Equatable {
  const PaymentMethodModel(
      {required this.id, required this.name, this.testMode = false});

  final String id;
  final String name;

  /// A test gateway: nothing real is charged.
  final bool testMode;

  factory PaymentMethodModel.fromJson(Map<String, dynamic> json) =>
      PaymentMethodModel(
        id: json['id'] as String,
        name: json['name'] as String,
        testMode: json['test_mode'] as bool? ?? false,
      );

  @override
  List<Object?> get props => [id, name, testMode];
}

abstract final class PaymentStatus {
  static const pending = 'pending';
  static const succeeded = 'succeeded';
  static const failed = 'failed';
  static const refunded = 'refunded';
}

/// One payment as the server reports it. The status is only ever what the
/// server learned from the gateway — the app never decides it.
class PaymentModel extends Equatable {
  const PaymentModel({
    required this.id,
    required this.status,
    required this.amount,
    required this.currency,
    required this.gateway,
    required this.createdAt,
    this.plan,
    this.testMode = false,
    this.receiptNumber,
    this.failureReason,
    this.paidAt,
    this.periodEndsAt,
  });

  final String id;
  final String status;
  final String? plan;
  final int amount;
  final String currency;
  final String gateway;
  final bool testMode;
  final String? receiptNumber;
  final String? failureReason;
  final DateTime createdAt;
  final DateTime? paidAt;
  final DateTime? periodEndsAt;

  bool get isPending => status == PaymentStatus.pending;
  bool get isSucceeded => status == PaymentStatus.succeeded;
  bool get isFailed => status == PaymentStatus.failed;

  factory PaymentModel.fromJson(Map<String, dynamic> json) {
    DateTime? date(String key) =>
        json[key] == null ? null : DateTime.tryParse(json[key] as String);

    return PaymentModel(
      id: json['id'] as String,
      status: json['status'] as String,
      plan: json['plan'] as String?,
      amount: (json['amount'] as num).toInt(),
      currency: json['currency'] as String? ?? 'IQD',
      gateway: json['gateway'] as String? ?? '',
      testMode: json['test_mode'] as bool? ?? false,
      receiptNumber: json['receipt_number'] as String?,
      failureReason: json['failure_reason'] as String?,
      createdAt: date('created_at') ?? DateTime.now(),
      paidAt: date('paid_at'),
      periodEndsAt: date('period_ends_at'),
    );
  }

  @override
  List<Object?> get props => [
        id,
        status,
        plan,
        amount,
        currency,
        gateway,
        testMode,
        receiptNumber,
        failureReason,
        createdAt,
        paidAt,
        periodEndsAt,
      ];
}

/// The result of starting a payment: the payment record plus where the
/// customer pays (null when the payment is already settled).
class StartedPayment {
  const StartedPayment({required this.payment, required this.checkoutUrl});

  final PaymentModel payment;
  final String? checkoutUrl;
}
