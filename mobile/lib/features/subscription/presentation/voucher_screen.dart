import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_button.dart';
import '../../profile/presentation/profile_controller.dart';
import '../data/voucher_code.dart';
import '../data/voucher_repository.dart';
import 'plan_format.dart';
import 'subscription_controller.dart';

/// "I have a voucher": type the 16 digits or scan the QR printed on the card, and
/// the paid plan switches on. The server does every check (single use, expiry,
/// revocation, guessing limits); this screen only forwards and explains.
class VoucherScreen extends ConsumerStatefulWidget {
  const VoucherScreen({super.key});

  @override
  ConsumerState<VoucherScreen> createState() => _VoucherScreenState();
}

class _VoucherScreenState extends ConsumerState<VoucherScreen> {
  final _controller = TextEditingController();
  bool _busy = false;
  String? _error;
  VoucherRedemption? _done;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  String _messageFor(AppLocalizations l10n, ApiException e) => switch (e.code) {
        'VOUCHER_INVALID' => l10n.voucherErrInvalid,
        'VOUCHER_INVALID_FORMAT' => l10n.voucherErrFormat,
        'VOUCHER_USED' => l10n.voucherErrUsed,
        'VOUCHER_EXPIRED' => l10n.voucherErrExpired,
        'VOUCHER_REVOKED' => l10n.voucherErrRevoked,
        'VOUCHER_THROTTLED' => l10n.voucherErrThrottled,
        'VOUCHER_LOCKED' => l10n.voucherErrLocked,
        'NETWORK_ERROR' => l10n.networkError,
        _ => e.message,
      };

  Future<void> _submit([String? scanned]) async {
    final l10n = AppLocalizations.of(context)!;
    final digits = VoucherCode.parse(scanned ?? _controller.text);

    if (digits == null || !VoucherCode.isValid(digits)) {
      setState(() => _error = l10n.voucherErrFormat);
      return;
    }

    setState(() {
      _busy = true;
      _error = null;
    });

    try {
      final result = await ref.read(voucherRepositoryProvider).redeem(digits);
      ref.invalidate(subscriptionProvider);
      ref.invalidate(profileProvider);
      if (mounted) setState(() => _done = result);
    } catch (e) {
      if (mounted) {
        setState(() => _error = _messageFor(l10n, asApiException(e)));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _scan() async {
    final raw = await Navigator.of(context).push<String>(
      MaterialPageRoute(builder: (_) => const _VoucherScanner()),
    );
    if (raw == null || !mounted) return;

    final digits = VoucherCode.parse(raw);
    if (digits == null) {
      setState(() => _error = AppLocalizations.of(context)!.voucherErrFormat);
      return;
    }
    _controller.text = VoucherCode.group(digits);
    await _submit(digits);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.voucherTitle)),
      body: SafeArea(
        child: _done != null ? _success(context, l10n, _done!) : _form(l10n),
      ),
    );
  }

  Widget _form(AppLocalizations l10n) {
    return ListView(
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        Text(l10n.voucherIntro,
            style: TextStyle(color: AppColors.ink600, height: 1.6)),
        const SizedBox(height: AppSpacing.xl),
        TextField(
          controller: _controller,
          keyboardType: TextInputType.number,
          textDirection: TextDirection.ltr,
          textAlign: TextAlign.center,
          autofocus: true,
          style: const TextStyle(
              fontSize: 22, fontWeight: FontWeight.w800, letterSpacing: 2),
          inputFormatters: [
            FilteringTextInputFormatter.allow(RegExp(r'[0-9٠-٩۰-۹ ]')),
            _GroupingFormatter(),
          ],
          decoration: InputDecoration(
            labelText: l10n.voucherFieldLabel,
            hintText: '0000 0000 0000 0000',
            errorText: _error,
            errorMaxLines: 3,
          ),
          onSubmitted: (_) => _submit(),
        ),
        const SizedBox(height: AppSpacing.lg),
        AppButton(
          label: l10n.voucherActivate,
          icon: Icons.check_circle_outline_rounded,
          style: AppButtonStyle.accent,
          loading: _busy,
          onPressed: _busy ? null : _submit,
        ),
        const SizedBox(height: AppSpacing.md),
        AppButton(
          label: l10n.voucherScan,
          icon: Icons.qr_code_scanner_rounded,
          style: AppButtonStyle.secondary,
          onPressed: _busy ? null : _scan,
        ),
        const SizedBox(height: AppSpacing.xl),
        Text(l10n.voucherNote,
            style: TextStyle(color: AppColors.ink500, fontSize: 13, height: 1.5)),
      ],
    );
  }

  Widget _success(
      BuildContext context, AppLocalizations l10n, VoucherRedemption r) {
    return Padding(
      padding: const EdgeInsets.all(AppSpacing.xl),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Icon(Icons.verified_rounded, size: 84, color: AppColors.statusSuccess),
          const SizedBox(height: AppSpacing.lg),
          Text(l10n.voucherSuccessTitle(r.planName),
              textAlign: TextAlign.center,
              style:
                  const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
          const SizedBox(height: AppSpacing.sm),
          Text(
            r.endsAt != null
                ? l10n.voucherSuccessUntil(formatDate(r.endsAt!.toLocal()))
                : l10n.voucherSuccessDays(r.days),
            textAlign: TextAlign.center,
            style: TextStyle(color: AppColors.ink600, height: 1.5),
          ),
          const SizedBox(height: AppSpacing.xxl),
          AppButton(
            label: l10n.done,
            onPressed: () => context.pop(),
          ),
        ],
      ),
    );
  }
}

/// Keeps the field as `0000 0000 0000 0000` while typing or pasting (digits only,
/// at most 16).
class _GroupingFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
      TextEditingValue oldValue, TextEditingValue newValue) {
    final buffer = StringBuffer();
    for (final rune in newValue.text.runes) {
      final ch = String.fromCharCode(rune);
      const arabic = '٠١٢٣٤٥٦٧٨٩';
      const persian = '۰۱۲۳۴۵۶۷۸۹';
      final a = arabic.indexOf(ch);
      final p = persian.indexOf(ch);
      if (a >= 0) {
        buffer.write(a);
      } else if (p >= 0) {
        buffer.write(p);
      } else if (rune >= 0x30 && rune <= 0x39) {
        buffer.write(ch);
      }
    }
    var digits = buffer.toString();
    if (digits.length > VoucherCode.length) {
      digits = digits.substring(0, VoucherCode.length);
    }
    final grouped = VoucherCode.group(digits);
    return TextEditingValue(
      text: grouped,
      selection: TextSelection.collapsed(offset: grouped.length),
    );
  }
}

/// Full-screen camera that returns the first QR's text.
class _VoucherScanner extends StatefulWidget {
  const _VoucherScanner();

  @override
  State<_VoucherScanner> createState() => _VoucherScannerState();
}

class _VoucherScannerState extends State<_VoucherScanner> {
  final _controller = MobileScannerController();
  bool _done = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) {
    if (_done || capture.barcodes.isEmpty) return;
    final raw = capture.barcodes.first.rawValue;
    if (raw == null || VoucherCode.parse(raw) == null) return;
    _done = true;
    Navigator.of(context).pop(raw);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text(l10n.voucherScan),
      ),
      body: Stack(
        fit: StackFit.expand,
        children: [
          MobileScanner(controller: _controller, onDetect: _onDetect),
          Align(
            alignment: Alignment.bottomCenter,
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.xl),
              child: Container(
                padding: const EdgeInsets.symmetric(
                    horizontal: AppSpacing.lg, vertical: AppSpacing.md),
                decoration: BoxDecoration(
                  color: Colors.black54,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Text(l10n.voucherScanHint,
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Colors.white)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
