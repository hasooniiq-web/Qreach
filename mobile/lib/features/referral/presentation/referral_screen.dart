import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/skeleton.dart';
import '../../subscription/presentation/subscription_controller.dart';
import '../data/referral_repository.dart';

final referralProvider = FutureProvider.autoDispose<ReferralInfo>((ref) {
  return ref.watch(referralRepositoryProvider).fetch();
});

/// The public site, in the language the customer reads the app in.
String _siteLink(Locale locale) =>
    'https://qreach.online/?lang=${locale.languageCode}';

/// Invite a friend: the customer's own code (copy / share), what it has earned,
/// and a place to type the code a friend gave them.
class ReferralScreen extends ConsumerWidget {
  const ReferralScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final async = ref.watch(referralProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.referralTitle)),
      body: async.when(
        loading: () => const SkeletonList(count: 3),
        error: (error, _) => ErrorState(
            error: error, onRetry: () => ref.invalidate(referralProvider)),
        data: (info) => _Body(info: info),
      ),
    );
  }
}

class _Body extends ConsumerStatefulWidget {
  const _Body({required this.info});

  final ReferralInfo info;

  @override
  ConsumerState<_Body> createState() => _BodyState();
}

class _BodyState extends ConsumerState<_Body> {
  final _controller = TextEditingController();
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  String _messageFor(AppLocalizations l10n, ApiException e) => switch (e.code) {
        'REFERRAL_INVALID' => l10n.referralErrInvalid,
        'REFERRAL_SELF' => l10n.referralErrSelf,
        'REFERRAL_ALREADY' => l10n.referralErrAlready,
        'REFERRAL_FULL' => l10n.referralErrFull,
        'REFERRAL_NOT_NEW' => l10n.referralErrNotNew,
        'REFERRAL_UNAVAILABLE' => l10n.referralErrUnavailable,
        'NETWORK_ERROR' => l10n.networkError,
        _ => e.message,
      };

  Future<void> _copy(AppLocalizations l10n) async {
    await Clipboard.setData(ClipboardData(text: widget.info.code));
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(l10n.referralCopied)));
  }

  Future<void> _share(AppLocalizations l10n) async {
    final text = l10n.referralShareText(
        widget.info.code, _siteLink(Localizations.localeOf(context)));
    try {
      await SharePlus.instance.share(ShareParams(text: text));
    } catch (_) {
      // No share sheet available: the copy button is still there.
    }
  }

  Future<void> _apply(AppLocalizations l10n) async {
    FocusScope.of(context).unfocus();
    final code = _controller.text.trim();
    if (code.length < 8) {
      setState(() => _error = l10n.referralErrInvalid);
      return;
    }

    setState(() {
      _busy = true;
      _error = null;
    });
    final messenger = ScaffoldMessenger.of(context);
    try {
      await ref.read(referralRepositoryProvider).apply(code);
      ref.invalidate(referralProvider);
      messenger.showSnackBar(SnackBar(content: Text(l10n.referralApplied)));
    } catch (e) {
      if (mounted) {
        setState(() => _error = _messageFor(l10n, asApiException(e)));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final info = widget.info;
    final days = info.rewardDays.toString();

    return ListView(
      padding: const EdgeInsets.all(AppSpacing.lg),
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      children: [
        if (!info.enabled)
          AppCard(
            child: Text(l10n.referralDisabledNote,
                style: TextStyle(color: AppColors.ink600, height: 1.5)),
          )
        else ...[
          Container(
            padding: const EdgeInsets.all(AppSpacing.xl),
            decoration: BoxDecoration(
              color: AppColors.brandBlack,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(l10n.referralHeadline(days),
                    style: const TextStyle(
                        color: AppColors.brandWhite,
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                        height: 1.4)),
                const SizedBox(height: AppSpacing.sm),
                Text(l10n.referralBody(days),
                    style: const TextStyle(color: Colors.white70, height: 1.6)),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          AppCard(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(l10n.referralYourCode,
                    style: TextStyle(color: AppColors.ink500, fontSize: 13)),
                const SizedBox(height: AppSpacing.sm),
                SelectableText(
                  info.code,
                  textAlign: TextAlign.center,
                  textDirection: TextDirection.ltr,
                  style: const TextStyle(
                      fontSize: 30, fontWeight: FontWeight.w800, letterSpacing: 4),
                ),
                const SizedBox(height: AppSpacing.lg),
                AppButton(
                  label: l10n.referralShareButton,
                  icon: Icons.ios_share_rounded,
                  style: AppButtonStyle.accent,
                  onPressed: () => _share(l10n),
                ),
                const SizedBox(height: AppSpacing.sm),
                AppButton(
                  label: l10n.referralCopy,
                  icon: Icons.copy_rounded,
                  style: AppButtonStyle.secondary,
                  onPressed: () => _copy(l10n),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(l10n.referralRemaining(info.remainingInvites),
              textAlign: TextAlign.center,
              style: TextStyle(color: AppColors.ink500, fontSize: 13)),
          const SizedBox(height: AppSpacing.lg),
          Row(
            children: [
              Expanded(
                  child: _Stat(
                      value: info.invited, label: l10n.referralStatInvited)),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                  child: _Stat(
                      value: info.rewarded,
                      label: l10n.referralStatSubscribed)),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                  child: _Stat(
                      value: info.earnedDays, label: l10n.referralStatDays)),
            ],
          ),
        ],
        if (info.canApply) ...[
          const SizedBox(height: AppSpacing.xl),
          AppCard(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(l10n.referralHaveCode,
                    style: const TextStyle(
                        fontWeight: FontWeight.w800, fontSize: 16)),
                const SizedBox(height: AppSpacing.md),
                TextField(
                  controller: _controller,
                  textDirection: TextDirection.ltr,
                  textCapitalization: TextCapitalization.characters,
                  autocorrect: false,
                  enableSuggestions: false,
                  inputFormatters: [
                    FilteringTextInputFormatter.allow(RegExp(r'[A-Za-z0-9\- ]')),
                    LengthLimitingTextInputFormatter(12),
                  ],
                  style: const TextStyle(
                      fontSize: 20, fontWeight: FontWeight.w700, letterSpacing: 3),
                  decoration: InputDecoration(
                    labelText: l10n.referralCodeField,
                    hintText: 'XXXX-XXXX',
                    errorText: _error,
                    errorMaxLines: 3,
                  ),
                  onSubmitted: (_) => _apply(l10n),
                ),
                const SizedBox(height: AppSpacing.md),
                AppButton(
                  label: l10n.referralApply,
                  loading: _busy,
                  onPressed: _busy ? null : () => _apply(l10n),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: AppSpacing.xl),
      ],
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.value, required this.label});

  final int value;
  final String label;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.sm, vertical: AppSpacing.md),
      child: Column(
        children: [
          Text('$value',
              style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
          const SizedBox(height: 2),
          Text(label,
              textAlign: TextAlign.center,
              style: TextStyle(color: AppColors.ink500, fontSize: 12)),
        ],
      ),
    );
  }
}
