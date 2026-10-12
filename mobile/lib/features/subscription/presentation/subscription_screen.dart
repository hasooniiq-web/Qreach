import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/design/motion.dart';
import '../../../core/design/tokens.dart';
import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../shared/widgets/app_button.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/skeleton.dart';
import '../data/subscription_models.dart';
import 'plan_format.dart';
import 'subscription_controller.dart';

/// Shows what the customer is on, what it costs, its status, what it allows,
/// and lets them request Pro. Purely a view of server state: what a plan
/// allows is enforced by the API, never decided here.
class SubscriptionScreen extends ConsumerWidget {
  const SubscriptionScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final async = ref.watch(subscriptionProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.subscriptionTitle)),
      body: async.when(
        loading: () => ListView(
          physics: const NeverScrollableScrollPhysics(),
          padding: const EdgeInsets.all(AppSpacing.lg),
          children: const [
            SkeletonBox(height: 170, radius: AppRadius.lg),
            SizedBox(height: AppSpacing.xl),
            SkeletonBox(height: 150, radius: AppRadius.md),
            SizedBox(height: AppSpacing.xl),
            SkeletonBox(height: 150, radius: AppRadius.md),
          ],
        ),
        error: (error, _) => ErrorState(
            error: error, onRetry: () => ref.invalidate(subscriptionProvider)),
        data: (info) => RefreshIndicator(
          onRefresh: () => ref.refresh(subscriptionProvider.future),
          child: ListView(
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: [
              if (info.hasLapsed && info.endsAt != null) ...[
                _LapsedBanner(date: formatDate(info.endsAt!)),
                const SizedBox(height: AppSpacing.md),
              ],
              if (info.inGrace && info.graceEndsAt != null) ...[
                _Banner(
                    text: l10n.planGraceBanner(formatDate(info.graceEndsAt!))),
                const SizedBox(height: AppSpacing.md),
              ],
              FadeSlideIn(child: _CurrentPlanCard(info: info)),
              if (info.endsAt != null &&
                  (info.canCancelRenewal || info.renewalCanceled)) ...[
                const SizedBox(height: AppSpacing.md),
                _RenewalControl(info: info),
              ],
              const SizedBox(height: AppSpacing.md),
              FadeSlideIn(index: 1, child: const _VoucherEntry()),
              const SizedBox(height: AppSpacing.xl),
              FadeSlideIn(
                index: 1,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _SectionTitle(l10n.planFeaturesTitle),
                    const SizedBox(height: AppSpacing.md),
                    _FeatureList(plan: info.plan),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.xl),
              FadeSlideIn(index: 2, child: _SectionTitle(l10n.plansTitle)),
              const SizedBox(height: AppSpacing.md),
              for (var i = 0; i < info.plans.length; i++) ...[
                FadeSlideIn(
                  index: 3 + i,
                  child: _PlanCard(plan: info.plans[i], info: info),
                ),
                const SizedBox(height: AppSpacing.md),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);

  final String text;

  @override
  Widget build(BuildContext context) =>
      Text(text, style: AppText.section(context).copyWith(fontSize: 18));
}

class _LapsedBanner extends StatelessWidget {
  const _LapsedBanner({required this.date});

  final String date;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return _Banner(text: l10n.planExpiredBanner(date));
  }
}

class _Banner extends StatelessWidget {
  const _Banner({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.brandYellow.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          const Icon(Icons.info_outline, size: 20),
          const SizedBox(width: AppSpacing.md),
          Expanded(child: Text(text)),
        ],
      ),
    );
  }
}

class _CurrentPlanCard extends StatelessWidget {
  const _CurrentPlanCard({required this.info});

  final SubscriptionInfo info;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final status = info.isDefault ? null : info.subscriptionStatus;

    return Container(
      padding: const EdgeInsets.all(AppSpacing.xl),
      decoration: BoxDecoration(
        color: AppColors.brandBlack,
        borderRadius: AppRadius.rLg,
        boxShadow: AppShadows.raised,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(l10n.currentPlan,
              style: const TextStyle(color: Colors.white70, fontSize: 13)),
          const SizedBox(height: AppSpacing.xs),
          Row(
            children: [
              Expanded(
                child: Text(info.plan.name,
                    style: const TextStyle(
                        color: AppColors.brandWhite,
                        fontSize: 28,
                        fontWeight: FontWeight.w800)),
              ),
              if (status != null)
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppColors.brandYellow,
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(subscriptionStatusLabel(context, status),
                      style: const TextStyle(
                          color: AppColors.brandBlack,
                          fontWeight: FontWeight.w700,
                          fontSize: 12)),
                ),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(planPriceLabel(context, info.plan),
              style: const TextStyle(
                  color: AppColors.brandYellow,
                  fontWeight: FontWeight.w700,
                  fontSize: 16)),
          const SizedBox(height: AppSpacing.xs),
          Text(
            info.isDefault || info.endsAt == null
                ? l10n.planDefaultFree
                : l10n.planEndsOn(formatDate(info.endsAt!)),
            style: const TextStyle(color: Colors.white70, fontSize: 13),
          ),
        ],
      ),
    );
  }
}

class _FeatureList extends StatelessWidget {
  const _FeatureList({required this.plan});

  final PlanModel plan;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final rows = <(String, bool)>[
      (l10n.featureMessaging, true),
      (l10n.featurePublicPhone, plan.allows(ContactType.phone)),
      (l10n.featurePublicInstagram, plan.allows(ContactType.instagram)),
      (l10n.featurePublicExtra, plan.allows(ContactType.other)),
    ];

    return AppCard(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.lg, vertical: AppSpacing.sm),
      child: Column(
        children: [
          for (final (label, allowed) in rows)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
              child: Row(
                children: [
                  // Check vs lock differ in shape, so availability never
                  // depends on color alone.
                  Icon(
                    allowed ? Icons.check_circle : Icons.lock_outline,
                    size: 20,
                    color: allowed ? AppColors.statusSuccess : AppColors.ink300,
                    semanticLabel: allowed
                        ? l10n.subStatusActive
                        : l10n.publicContactLockedTitle,
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: Text(label,
                        style: TextStyle(
                            color:
                                allowed ? AppColors.ink900 : AppColors.ink400)),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _PlanCard extends StatelessWidget {
  const _PlanCard({required this.plan, required this.info});

  final PlanModel plan;
  final SubscriptionInfo info;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final isCurrent = plan.slug == info.plan.slug;

    return AppCard(
      padding: const EdgeInsets.all(AppSpacing.lg),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(plan.name,
                    style: Theme.of(context)
                        .textTheme
                        .titleMedium
                        ?.copyWith(fontWeight: FontWeight.w700)),
              ),
              if (isCurrent)
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppColors.onSurface,
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(l10n.planYourPlan,
                      style: TextStyle(color: AppColors.surface, fontSize: 12)),
                ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(planPriceLabel(context, plan),
              style: const TextStyle(fontWeight: FontWeight.w700)),
          if (plan.description != null) ...[
            const SizedBox(height: AppSpacing.sm),
            Text(plan.description!, style: TextStyle(color: AppColors.ink500)),
          ],
          // Also offered on the current paid plan inside its grace week: renewing
          // then loses nothing.
          if ((!isCurrent || info.inGrace) && !plan.isFree) ...[
            const SizedBox(height: AppSpacing.lg),
            _UpgradeButton(
                plan: plan,
                pending: info.pendingUpgrade,
                renew: isCurrent && info.inGrace),
          ],
        ],
      ),
    );
  }
}

/// Opens the checkout for a plan. (Before electronic payments this sent an
/// "upgrade request" for an admin to approve; a request made earlier can still
/// be pending and is shown as such.)
class _UpgradeButton extends StatelessWidget {
  const _UpgradeButton(
      {required this.plan, required this.pending, this.renew = false});

  final PlanModel plan;
  final bool pending;
  final bool renew;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    if (pending) {
      return AppButton(
        label: l10n.upgradePending,
        icon: Icons.schedule_rounded,
        style: AppButtonStyle.secondary,
        onPressed: null,
      );
    }

    return AppButton(
      label: renew ? l10n.renewNow : l10n.paySubscribeButton,
      icon: Icons.lock_outline_rounded,
      onPressed: () => context.push('/subscription/checkout/${plan.slug}'),
    );
  }
}


/// Stop (or resume) the yearly renewal. Stopping keeps the plan working until the
/// end of the period already paid for — no day is lost.
class _RenewalControl extends ConsumerStatefulWidget {
  const _RenewalControl({required this.info});

  final SubscriptionInfo info;

  @override
  ConsumerState<_RenewalControl> createState() => _RenewalControlState();
}

class _RenewalControlState extends ConsumerState<_RenewalControl> {
  bool _busy = false;

  Future<void> _cancel() async {
    final l10n = AppLocalizations.of(context)!;
    final date = formatDate(widget.info.endsAt!);
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n.cancelAnnualTitle),
        content: Text(l10n.cancelAnnualBody(date),
            style: const TextStyle(height: 1.5)),
        actionsAlignment: MainAxisAlignment.spaceBetween,
        actions: [
          TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: Text(l10n.keepSubscription)),
          TextButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: Text(l10n.cancelAnnualConfirm,
                style: TextStyle(
                    color: AppColors.statusDanger,
                    fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    await _run(() => ref.read(subscriptionRepositoryProvider).cancelRenewal(),
        l10n.renewalCanceledDone);
  }

  Future<void> _resume() async {
    final l10n = AppLocalizations.of(context)!;
    await _run(() => ref.read(subscriptionRepositoryProvider).resumeRenewal(),
        l10n.renewalResumedDone);
  }

  Future<void> _run(Future<void> Function() action, String done) async {
    setState(() => _busy = true);
    final messenger = ScaffoldMessenger.of(context);
    try {
      await action();
      ref.invalidate(subscriptionProvider);
      messenger.showSnackBar(SnackBar(content: Text(done)));
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
    final info = widget.info;

    if (info.renewalCanceled) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _Banner(text: l10n.renewalCanceledNote(formatDate(info.endsAt!))),
          const SizedBox(height: AppSpacing.md),
          AppButton(
            label: l10n.resumeRenewal,
            icon: Icons.autorenew_rounded,
            style: AppButtonStyle.secondary,
            loading: _busy,
            onPressed: _busy ? null : _resume,
          ),
        ],
      );
    }

    return AppButton(
      label: l10n.cancelAnnualPlan,
      icon: Icons.event_busy_rounded,
      style: AppButtonStyle.secondary,
      loading: _busy,
      onPressed: _busy ? null : _cancel,
    );
  }
}


/// The way in to the voucher screen: a tile under the current plan.
class _VoucherEntry extends StatelessWidget {
  const _VoucherEntry();

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return AppCard(
      onTap: () => context.push('/subscription/voucher'),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: AppColors.brandYellow.withValues(alpha: 0.25),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(Icons.confirmation_number_outlined,
                color: AppColors.ink900),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(l10n.voucherEntryTitle,
                    style: const TextStyle(fontWeight: FontWeight.w700)),
                const SizedBox(height: 2),
                Text(l10n.voucherEntryBody,
                    style: TextStyle(color: AppColors.ink500, fontSize: 12.5)),
              ],
            ),
          ),
          Icon(Icons.chevron_right_rounded, color: AppColors.ink300),
        ],
      ),
    );
  }
}
