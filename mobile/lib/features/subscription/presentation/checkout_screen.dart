import 'dart:math';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/design/motion.dart';
import '../../../core/design/tokens.dart';
import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/skeleton.dart';
import '../data/payment_models.dart';
import '../data/subscription_models.dart';
import 'plan_format.dart';
import 'subscription_controller.dart';

/// The payment methods the server offers right now (enabled + configured by an
/// admin). Fetched fresh each time the checkout opens.
final paymentMethodsProvider =
    FutureProvider.autoDispose<List<PaymentMethodModel>>((ref) {
  return ref.watch(paymentRepositoryProvider).methods();
});

/// Select a plan -> review price and benefits -> choose a payment method -> pay.
/// What is charged is decided by the server from the plan; this screen only
/// sends the plan and the chosen method.
class CheckoutScreen extends ConsumerStatefulWidget {
  const CheckoutScreen({super.key, required this.planSlug, this.openUrl});

  final String planSlug;

  /// Opens the provider's payment page. Defaults to an in-app browser; tests
  /// pass a fake.
  final Future<bool> Function(Uri url)? openUrl;

  @override
  ConsumerState<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends ConsumerState<CheckoutScreen> {
  String? _methodId;
  bool _busy = false;

  /// One key per checkout visit: tapping "Pay" twice (or retrying after a
  /// network error) returns the same payment instead of creating another.
  final String _idempotencyKey = _newKey();

  static String _newKey() {
    final r = Random.secure();
    return List.generate(
        16, (_) => r.nextInt(256).toRadixString(16).padLeft(2, '0')).join();
  }

  Future<void> _pay(PlanModel plan, List<PaymentMethodModel> methods) async {
    final l10n = AppLocalizations.of(context)!;
    final method = methods.firstWhere((m) => m.id == _methodId);

    setState(() => _busy = true);
    final messenger = ScaffoldMessenger.of(context);
    final router = GoRouter.of(context);
    try {
      final started = await ref.read(paymentRepositoryProvider).start(
            planSlug: plan.slug,
            gateway: method.id,
            idempotencyKey: _idempotencyKey,
          );

      final url = started.checkoutUrl;
      if (url != null) {
        final open = widget.openUrl ??
            (Uri u) => launchUrl(u, mode: LaunchMode.inAppBrowserView);
        final opened = await open(Uri.parse(url));
        if (!opened) {
          messenger.showSnackBar(SnackBar(content: Text(l10n.payOpenFailed)));
        }
      }
      // The status screen waits for the server to confirm; nothing is
      // assumed from the browser having been opened or closed.
      router.pushReplacement('/subscription/payment/${started.payment.id}');
    } catch (e) {
      messenger
          .showSnackBar(SnackBar(content: Text(asApiException(e).message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final subscription = ref.watch(subscriptionProvider);
    final methods = ref.watch(paymentMethodsProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.payCheckoutTitle)),
      body: subscription.when(
        loading: () => ListView(
          padding: const EdgeInsets.all(AppSpacing.lg),
          children: const [
            SkeletonBox(height: 150, radius: AppRadius.lg),
            SizedBox(height: AppSpacing.xl),
            SkeletonBox(height: 120, radius: AppRadius.md),
          ],
        ),
        error: (e, _) => ErrorState(
            error: e, onRetry: () => ref.invalidate(subscriptionProvider)),
        data: (info) {
          final plan =
              info.plans.where((p) => p.slug == widget.planSlug).firstOrNull;
          if (plan == null) {
            return ErrorState(
                error: StateError('plan'),
                onRetry: () => ref.invalidate(subscriptionProvider));
          }
          return _body(context, plan, methods);
        },
      ),
    );
  }

  Widget _body(BuildContext context, PlanModel plan,
      AsyncValue<List<PaymentMethodModel>> methodsAsync) {
    final l10n = AppLocalizations.of(context)!;
    final methods = methodsAsync.valueOrNull ?? const <PaymentMethodModel>[];
    // Pre-select when there is exactly one way to pay.
    if (_methodId == null && methods.length == 1) _methodId = methods.first.id;
    final canPay = _methodId != null && !_busy;

    return ListView(
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        FadeSlideIn(
          child: Container(
            padding: const EdgeInsets.all(AppSpacing.xl),
            decoration: BoxDecoration(
              color: AppColors.brandBlack,
              borderRadius: AppRadius.rLg,
              boxShadow: AppShadows.raised,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(l10n.payReviewTitle,
                    style:
                        const TextStyle(color: Colors.white70, fontSize: 13)),
                const SizedBox(height: AppSpacing.xs),
                Text(plan.name,
                    style: const TextStyle(
                        color: AppColors.brandWhite,
                        fontSize: 26,
                        fontWeight: FontWeight.w800)),
                const SizedBox(height: AppSpacing.sm),
                Text(planPriceLabel(context, plan),
                    key: const ValueKey('checkout-price'),
                    style: const TextStyle(
                        color: AppColors.brandYellow,
                        fontWeight: FontWeight.w700,
                        fontSize: 18)),
              ],
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.xl),
        Text(l10n.payBenefitsTitle,
            style: AppText.section(context).copyWith(fontSize: 18)),
        const SizedBox(height: AppSpacing.md),
        _Benefits(plan: plan),
        const SizedBox(height: AppSpacing.xl),
        Text(l10n.payMethodTitle,
            style: AppText.section(context).copyWith(fontSize: 18)),
        const SizedBox(height: AppSpacing.md),
        methodsAsync.when(
          loading: () => const SkeletonBox(height: 64, radius: AppRadius.md),
          error: (e, _) => ErrorState(
              error: e, onRetry: () => ref.invalidate(paymentMethodsProvider)),
          data: (list) => list.isEmpty
              ? AppCard(
                  child: Text(l10n.payNoMethods,
                      key: const ValueKey('no-methods'),
                      style: TextStyle(color: AppColors.ink500)))
              : Column(
                  children: [
                    for (final m in list)
                      _MethodTile(
                        method: m,
                        selected: m.id == _methodId,
                        onTap: _busy
                            ? null
                            : () => setState(() => _methodId = m.id),
                      ),
                  ],
                ),
        ),
        const SizedBox(height: AppSpacing.lg),
        Text(l10n.payNote,
            style:
                TextStyle(color: AppColors.ink500, fontSize: 13, height: 1.5)),
        const SizedBox(height: AppSpacing.lg),
        AppButton(
          label: l10n.payNow,
          icon: Icons.lock_outline_rounded,
          loading: _busy,
          onPressed: canPay ? () => _pay(plan, methods) : null,
        ),
      ],
    );
  }
}

class _Benefits extends StatelessWidget {
  const _Benefits({required this.plan});

  final PlanModel plan;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final rows = <String>[
      l10n.featureMessaging,
      if (plan.allows(ContactType.phone)) l10n.featurePublicPhone,
      if (plan.allows(ContactType.instagram)) l10n.featurePublicInstagram,
      if (plan.allows(ContactType.other)) l10n.featurePublicExtra,
    ];

    return AppCard(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.lg, vertical: AppSpacing.sm),
      child: Column(
        children: [
          for (final label in rows)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
              child: Row(
                children: [
                  Icon(Icons.check_circle,
                      size: 20, color: AppColors.statusSuccess),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(child: Text(label)),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _MethodTile extends StatelessWidget {
  const _MethodTile(
      {required this.method, required this.selected, required this.onTap});

  final PaymentMethodModel method;
  final bool selected;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: Semantics(
        button: true,
        selected: selected,
        label: method.name,
        child: InkWell(
          onTap: onTap,
          borderRadius: AppRadius.rMd,
          child: Container(
            padding: const EdgeInsets.all(AppSpacing.lg),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: AppRadius.rMd,
              border: Border.all(
                  color: selected ? AppColors.brandYellow : AppColors.ink200,
                  width: selected ? 2 : 1),
            ),
            child: Row(
              children: [
                Icon(Icons.credit_card_rounded, color: AppColors.ink700),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Text(method.name,
                      style: const TextStyle(fontWeight: FontWeight.w600)),
                ),
                if (method.testMode)
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppColors.brandYellow.withValues(alpha: 0.25),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(l10n.payTestBadge,
                        style: const TextStyle(
                            fontSize: 11, fontWeight: FontWeight.w700)),
                  ),
                const SizedBox(width: AppSpacing.sm),
                Icon(
                    selected
                        ? Icons.radio_button_checked
                        : Icons.radio_button_off,
                    color: selected ? AppColors.brandBlack : AppColors.ink300),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
