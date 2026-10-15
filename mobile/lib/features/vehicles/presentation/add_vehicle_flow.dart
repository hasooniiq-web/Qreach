import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../l10n/app_localizations.dart';
import '../../subscription/presentation/subscription_controller.dart';
import 'vehicle_controller.dart';

/// The free plan keeps one vehicle and Plus three. Before the form is opened
/// the plan is asked, so nobody types a whole vehicle only to be refused; the
/// server still enforces it (the form also handles `VEHICLE_LIMIT`).
Future<void> startAddVehicle(BuildContext context, WidgetRef ref) async {
  final count = ref.read(vehicleListControllerProvider).valueOrNull?.length;

  if (count != null && count > 0) {
    try {
      final info = await ref.read(subscriptionProvider.future);
      final max = info.plan.maxVehicles;
      if (max != null && count >= max) {
        if (!context.mounted) return;
        final plusMax = info.plans
            .where((p) => !p.isFree)
            .map((p) => p.maxVehicles)
            .whereType<int>()
            .firstOrNull;
        await showVehicleLimitDialog(context,
            free: info.plan.isFree, max: info.plan.isFree ? plusMax : max);
        return;
      }
    } catch (_) {
      // Could not ask: let the server decide when the form is saved.
    }
  }

  if (context.mounted) context.push('/vehicles/new');
}

/// "Activate the plan to add another", with a way straight to the subscription.
Future<void> showVehicleLimitDialog(
  BuildContext context, {
  required bool free,
  int? max,
}) {
  final l10n = AppLocalizations.of(context)!;

  return showDialog<void>(
    context: context,
    builder: (context) => AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      icon: Container(
        width: 64,
        height: 64,
        decoration: BoxDecoration(
          color: AppColors.brandYellow.withValues(alpha: 0.25),
          shape: BoxShape.circle,
        ),
        child: const Icon(Icons.workspace_premium_rounded,
            size: 34, color: AppColors.brandBlack),
      ),
      title: Text(l10n.vehicleLimitTitle, textAlign: TextAlign.center),
      content: Text(
        free
            ? l10n.vehicleLimitFree(max ?? 3)
            : l10n.vehicleLimitPlus(max ?? 3),
        textAlign: TextAlign.center,
        style: const TextStyle(fontSize: 15, height: 1.6),
      ),
      actionsAlignment: MainAxisAlignment.spaceBetween,
      actions: [
        TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: Text(l10n.cancel)),
        if (free)
          FilledButton(
            onPressed: () {
              Navigator.of(context).pop();
              context.push('/subscription');
            },
            child: Text(l10n.vehicleLimitGo),
          ),
      ],
    ),
  );
}
