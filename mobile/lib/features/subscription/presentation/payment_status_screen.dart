import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart' hide TextDirection;

import '../../../core/design/tokens.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/error_state.dart';
import '../data/payment_models.dart';
import 'plan_format.dart';
import 'subscription_controller.dart';

/// Waits for the server to confirm a payment, then shows the receipt (or the
/// failure). The result is only ever read from the server: closing the payment
/// page, or reaching this screen, proves nothing by itself.
class PaymentStatusScreen extends ConsumerStatefulWidget {
  const PaymentStatusScreen({
    super.key,
    required this.paymentId,
    this.pollInterval = const Duration(seconds: 3),
  });

  final String paymentId;
  final Duration pollInterval;

  @override
  ConsumerState<PaymentStatusScreen> createState() =>
      _PaymentStatusScreenState();
}

class _PaymentStatusScreenState extends ConsumerState<PaymentStatusScreen>
    with WidgetsBindingObserver {
  PaymentModel? _payment;
  Object? _error;
  Timer? _timer;
  bool _fetching = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _refresh();
    _timer = Timer.periodic(widget.pollInterval, (_) {
      if (_payment == null || _payment!.isPending) _refresh();
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _timer?.cancel();
    super.dispose();
  }

  // Back from the payment page: check straight away instead of waiting a tick.
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _refresh();
  }

  Future<void> _refresh() async {
    if (_fetching || !mounted) return;
    _fetching = true;
    try {
      final payment =
          await ref.read(paymentRepositoryProvider).fetch(widget.paymentId);
      if (!mounted) return;
      final becameSettled =
          payment.isSucceeded && !(_payment?.isSucceeded ?? false);
      setState(() {
        _payment = payment;
        _error = null;
      });
      if (becameSettled) ref.invalidate(subscriptionProvider);
    } catch (e) {
      // A dropped connection while polling is not a failed payment: keep
      // waiting and retry on the next tick (shown only if nothing loaded yet).
      if (mounted && _payment == null) setState(() => _error = e);
    } finally {
      _fetching = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final payment = _payment;

    Widget body;
    if (payment == null && _error != null) {
      body = ErrorState(error: _error!, onRetry: _refresh);
    } else if (payment == null || payment.isPending) {
      body = const _Waiting();
    } else if (payment.isSucceeded) {
      body = _Receipt(payment: payment);
    } else {
      body = _Failed(payment: payment);
    }

    return PopScope(
      // The status screen replaces the checkout, so "back" leaves the flow.
      child: Scaffold(
        appBar: AppBar(title: Text(l10n.payStatusTitle)),
        body: AnimatedSwitcher(
          duration: const Duration(milliseconds: 250),
          child: body,
        ),
      ),
    );
  }
}

class _Waiting extends StatelessWidget {
  const _Waiting();

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Center(
      key: const ValueKey('waiting'),
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(
                width: 44,
                height: 44,
                child: CircularProgressIndicator(strokeWidth: 3.5)),
            const SizedBox(height: AppSpacing.xl),
            Text(l10n.payChecking,
                textAlign: TextAlign.center,
                style:
                    const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
            const SizedBox(height: AppSpacing.sm),
            Text(l10n.payCheckingHint,
                textAlign: TextAlign.center,
                style: TextStyle(color: AppColors.ink500, height: 1.5)),
          ],
        ),
      ),
    );
  }
}

class _Receipt extends StatelessWidget {
  const _Receipt({required this.payment});

  final PaymentModel payment;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final amount = '${NumberFormat('#,###', 'en').format(payment.amount)} '
        '${payment.currency == 'IQD' ? 'د.ع' : payment.currency}';
    final dateFormat = DateFormat('yyyy-MM-dd HH:mm', 'en');

    return ListView(
      key: const ValueKey('receipt'),
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        const SizedBox(height: AppSpacing.lg),
        Icon(Icons.check_circle_rounded,
            size: 72, color: AppColors.statusSuccess),
        const SizedBox(height: AppSpacing.md),
        Text(l10n.paySuccessTitle,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
        const SizedBox(height: AppSpacing.xl),
        AppCard(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Column(
            children: [
              Align(
                alignment: AlignmentDirectional.centerStart,
                child: Text(l10n.payReceiptTitle,
                    style: AppText.section(context).copyWith(fontSize: 16)),
              ),
              const SizedBox(height: AppSpacing.md),
              _Row(l10n.payReceiptNumber, payment.receiptNumber ?? '—',
                  ltr: true),
              _Row(l10n.payReceiptPlan, payment.plan ?? '—'),
              _Row(l10n.payReceiptAmount, amount),
              _Row(l10n.payReceiptMethod, payment.gateway),
              if (payment.paidAt != null)
                _Row(l10n.payReceiptDate,
                    dateFormat.format(payment.paidAt!.toLocal()),
                    ltr: true),
              if (payment.periodEndsAt != null)
                _Row(l10n.payReceiptValidUntil,
                    formatDate(payment.periodEndsAt!.toLocal()),
                    ltr: true),
            ],
          ),
        ),
        if (payment.testMode) ...[
          const SizedBox(height: AppSpacing.md),
          Text(l10n.payTestNotice,
              textAlign: TextAlign.center,
              style: TextStyle(color: AppColors.ink500, fontSize: 13)),
        ],
        const SizedBox(height: AppSpacing.xl),
        AppButton(
          label: l10n.payDone,
          onPressed: () =>
              context.canPop() ? context.pop() : context.go('/home'),
        ),
      ],
    );
  }
}

class _Row extends StatelessWidget {
  const _Row(this.label, this.value, {this.ltr = false});

  final String label;
  final String value;
  final bool ltr;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(color: AppColors.ink500)),
          const SizedBox(width: AppSpacing.lg),
          Expanded(
            child: Text(value,
                textAlign: TextAlign.end,
                textDirection: ltr ? TextDirection.ltr : null,
                style: const TextStyle(fontWeight: FontWeight.w600)),
          ),
        ],
      ),
    );
  }
}

class _Failed extends StatelessWidget {
  const _Failed({required this.payment});

  final PaymentModel payment;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final detail = payment.failureReason == 'expired'
        ? l10n.payFailedExpired
        : l10n.payFailedHint;

    return Center(
      key: const ValueKey('failed'),
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Icon(Icons.error_outline_rounded,
                size: 72, color: AppColors.statusDanger),
            const SizedBox(height: AppSpacing.md),
            Text(l10n.payFailedTitle,
                textAlign: TextAlign.center,
                style:
                    const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
            const SizedBox(height: AppSpacing.sm),
            Text(detail,
                textAlign: TextAlign.center,
                style: TextStyle(color: AppColors.ink500, height: 1.5)),
            const SizedBox(height: AppSpacing.xl),
            AppButton(
              label: l10n.payTryAgain,
              onPressed: () => context.canPop()
                  ? context.pop()
                  : context.go('/subscription'),
            ),
          ],
        ),
      ),
    );
  }
}
