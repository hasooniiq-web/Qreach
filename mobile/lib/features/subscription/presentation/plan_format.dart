import 'package:flutter/widgets.dart';
import 'package:intl/intl.dart';

import '../../../l10n/app_localizations.dart';
import '../data/subscription_models.dart';

final _amount = NumberFormat('#,###', 'en');

/// "مجاني" for free plans, otherwise "25,000 د.ع / سنة" (or "/ N days").
/// Latin digits on purpose — matches the dashboard so the same price reads
/// identically everywhere.
String planPriceLabel(BuildContext context, PlanModel plan) {
  final l10n = AppLocalizations.of(context)!;
  if (plan.isFree || plan.price == 0) return l10n.priceFree;

  final amount = _amount.format(plan.price);
  return plan.billingPeriodDays == 365
      ? l10n.priceAmountYear(amount)
      : l10n.priceAmountDays(amount, plan.billingPeriodDays);
}

String subscriptionStatusLabel(BuildContext context, String status) {
  final l10n = AppLocalizations.of(context)!;
  return switch (status) {
    'active' => l10n.subStatusActive,
    'grace' => l10n.subStatusGrace,
    'trialing' => l10n.subStatusTrialing,
    'canceled' => l10n.subStatusCanceled,
    'past_due' => l10n.subStatusPastDue,
    _ => l10n.subStatusExpired,
  };
}

String formatDate(DateTime date) => DateFormat('yyyy-MM-dd', 'en').format(date);
