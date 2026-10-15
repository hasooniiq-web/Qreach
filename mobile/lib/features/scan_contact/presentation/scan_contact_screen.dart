import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../contacts/presentation/contact_controller.dart';
import '../../tags/presentation/scan_screen.dart' show ScanOverlay;
import '../../vehicles/presentation/vehicle_type_labels.dart';
import '../data/scan_contact_repository.dart';

String scanReasonLabel(AppLocalizations l10n, String key) => switch (key) {
      'blocking' => l10n.reasonBlocking,
      'damage' => l10n.reasonDamage,
      'malfunction' => l10n.reasonMalfunction,
      'safety' => l10n.reasonSafety,
      'assistance' => l10n.reasonAssistance,
      'lights' => l10n.reasonLights,
      _ => key,
    };

IconData _reasonIcon(String key) => switch (key) {
      'blocking' => Icons.block_rounded,
      'damage' => Icons.car_crash_outlined,
      'malfunction' => Icons.build_circle_outlined,
      'safety' => Icons.health_and_safety_outlined,
      'assistance' => Icons.support_agent_rounded,
      'lights' => Icons.lightbulb_outline_rounded,
      _ => Icons.report_gmailerrorred_rounded,
    };

/// Scan the QR code of another customer's vehicle from inside the app: see what
/// the owner shows publicly, pick one of six reasons, and carry on the
/// conversation here. A refused code is explained in the middle of the screen
/// and the scanner is left, so the same code is not sent again and again.
class ScanContactScreen extends ConsumerStatefulWidget {
  const ScanContactScreen({super.key});

  @override
  ConsumerState<ScanContactScreen> createState() => _ScanContactScreenState();
}

class _ScanContactScreenState extends ConsumerState<ScanContactScreen> {
  final _controller = MobileScannerController();
  bool _busy = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_busy || capture.barcodes.isEmpty) return;
    final raw = capture.barcodes.first.rawValue;
    if (raw == null || raw.isEmpty) return;
    await _lookup(raw);
  }

  Future<void> _lookup(String raw) async {
    if (_busy) return;
    setState(() => _busy = true);
    HapticFeedback.mediumImpact();
    try {
      await _controller.stop();
    } catch (_) {}

    ScannedVehicle vehicle;
    try {
      vehicle = await ref.read(scanContactRepositoryProvider).lookup(raw);
    } catch (e) {
      if (!mounted) return;
      await _showRefusal(asApiException(e).message);
      if (mounted) context.pop();
      return;
    }

    if (!mounted) return;
    final conversationId = await showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => _ReportSheet(vehicle: vehicle),
    );

    if (!mounted) return;
    if (conversationId == null) {
      // Cancelled: leave, rather than reading the same code again.
      context.pop();
      return;
    }

    ref.invalidate(contactsProvider);
    context.pushReplacement('/contacts/$conversationId');
  }

  Future<void> _showRefusal(String message) {
    final l10n = AppLocalizations.of(context)!;
    return showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        icon: Container(
          width: 64,
          height: 64,
          decoration: BoxDecoration(
            color: AppColors.statusDanger.withValues(alpha: 0.12),
            shape: BoxShape.circle,
          ),
          child: Icon(Icons.error_outline_rounded,
              size: 36, color: AppColors.statusDanger),
        ),
        title: Text(l10n.scanErrorTitle, textAlign: TextAlign.center),
        content: Text(message,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 15, height: 1.6)),
        actionsAlignment: MainAxisAlignment.center,
        actions: [
          FilledButton(
            onPressed: () => Navigator.of(context).pop(),
            child: Text(l10n.scanErrorClose),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      backgroundColor: AppColors.brandBlack,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        foregroundColor: AppColors.brandWhite,
        elevation: 0,
        actions: [
          IconButton(
            tooltip: l10n.scanTorch,
            icon: const Icon(Icons.flashlight_on_outlined),
            onPressed: () async {
              try {
                await _controller.toggleTorch();
              } catch (_) {}
            },
          ),
        ],
      ),
      body: Stack(
        children: [
          MobileScanner(
            controller: _controller,
            onDetect: _onDetect,
            errorBuilder: (context, error, child) => Center(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.no_photography_outlined,
                        color: AppColors.brandWhite, size: 48),
                    const SizedBox(height: 16),
                    Text(l10n.scanCameraError,
                        style: const TextStyle(
                            color: AppColors.brandWhite,
                            fontSize: 18,
                            fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    Text(l10n.scanCameraPermission,
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Colors.white70)),
                  ],
                ),
              ),
            ),
          ),
          const ScanOverlay(),
          Positioned(
            left: 24,
            right: 24,
            bottom: 56,
            child: Column(
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.6),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(l10n.scanVehicleHint,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                          color: AppColors.brandWhite, fontSize: 15)),
                ),
                if (_busy) ...[
                  const SizedBox(height: 16),
                  const CircularProgressIndicator(color: AppColors.brandYellow),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// What the owner shows, the six reasons, and the send button.
class _ReportSheet extends ConsumerStatefulWidget {
  const _ReportSheet({required this.vehicle});

  final ScannedVehicle vehicle;

  @override
  ConsumerState<_ReportSheet> createState() => _ReportSheetState();
}

class _ReportSheetState extends ConsumerState<_ReportSheet> {
  String? _reason;
  bool _sending = false;
  String? _error;

  Future<void> _send() async {
    final reason = _reason;
    if (reason == null || _sending) return;
    setState(() {
      _sending = true;
      _error = null;
    });
    try {
      final id = await ref
          .read(scanContactRepositoryProvider)
          .contact(token: widget.vehicle.token, reason: reason);
      if (mounted) Navigator.of(context).pop(id);
    } catch (e) {
      if (mounted) {
        setState(() {
          _sending = false;
          _error = asApiException(e).message;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final v = widget.vehicle;
    final facts = <String>[
      if (v.make?.isNotEmpty == true && v.nickname?.isNotEmpty == true) v.make!,
      if (v.model?.isNotEmpty == true && v.nickname?.isNotEmpty == true)
        v.model!,
      if (v.vehicleType?.isNotEmpty == true)
        vehicleTypeLabel(context, v.vehicleType),
      if (v.color?.isNotEmpty == true) v.color!,
    ];

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(
            AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.xl),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Container(
              padding: const EdgeInsets.all(AppSpacing.lg),
              decoration: BoxDecoration(
                color: AppColors.brandBlack,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (v.title != null)
                    Text(v.title!,
                        style: const TextStyle(
                            color: AppColors.brandWhite,
                            fontSize: 19,
                            fontWeight: FontWeight.w800)),
                  if (facts.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 6,
                      runSpacing: 6,
                      children: [
                        for (final f in facts)
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 10, vertical: 3),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Text(f,
                                style: const TextStyle(
                                    color: AppColors.brandWhite,
                                    fontSize: 12)),
                          ),
                      ],
                    ),
                  ],
                  if (v.ownerName?.isNotEmpty == true) ...[
                    const SizedBox(height: 8),
                    Text(l10n.scanOwnerLine(v.ownerName!),
                        style: const TextStyle(color: Colors.white70)),
                  ],
                ],
              ),
            ),
            if (v.contacts.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.lg),
              Text(l10n.scanContactsTitle,
                  style: const TextStyle(fontWeight: FontWeight.w800)),
              const SizedBox(height: AppSpacing.sm),
              for (final c in v.contacts)
                ListTile(
                  dense: true,
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.call_outlined),
                  title: Text(c.label),
                  subtitle: Text(c.value, textDirection: TextDirection.ltr),
                  onTap: c.href == null
                      ? null
                      : () => launchUrl(Uri.parse(c.href!),
                          mode: LaunchMode.externalApplication),
                ),
            ],
            const SizedBox(height: AppSpacing.lg),
            Text(l10n.scanReportTitle,
                style:
                    const TextStyle(fontWeight: FontWeight.w800, fontSize: 17)),
            const SizedBox(height: 2),
            Text(l10n.scanReportSubtitle,
                style: TextStyle(color: AppColors.ink500, fontSize: 12.5)),
            if (v.conversationId != null) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(l10n.scanReportExisting,
                  style: TextStyle(color: AppColors.ink600, fontSize: 12.5)),
            ],
            const SizedBox(height: AppSpacing.md),
            GridView.count(
              crossAxisCount: 2,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: AppSpacing.sm,
              crossAxisSpacing: AppSpacing.sm,
              childAspectRatio: 2.6,
              children: [
                for (final key in scanReasons)
                  _ReasonCard(
                    icon: _reasonIcon(key),
                    label: scanReasonLabel(l10n, key),
                    selected: _reason == key,
                    onTap: _sending ? null : () => setState(() => _reason = key),
                  ),
              ],
            ),
            if (_error != null) ...[
              const SizedBox(height: AppSpacing.md),
              Text(_error!,
                  textAlign: TextAlign.center,
                  style: TextStyle(color: AppColors.statusDanger)),
            ],
            const SizedBox(height: AppSpacing.lg),
            FilledButton(
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFFDC2626),
                foregroundColor: Colors.white,
                minimumSize: const Size.fromHeight(54),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14)),
              ),
              onPressed: _reason == null || _sending ? null : _send,
              child: _sending
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                          strokeWidth: 2.4, color: Colors.white))
                  : Text(l10n.scanReportSend,
                      style: const TextStyle(
                          fontSize: 17, fontWeight: FontWeight.w800)),
            ),
            const SizedBox(height: AppSpacing.sm),
            TextButton(
              onPressed:
                  _sending ? null : () => Navigator.of(context).pop(null),
              child: Text(l10n.cancel),
            ),
          ],
        ),
      ),
    );
  }
}

class _ReasonCard extends StatelessWidget {
  const _ReasonCard({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: selected ? AppColors.brandYellow : AppColors.surface,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
        side: BorderSide(
            color: selected ? AppColors.brandYellow : AppColors.ink200,
            width: 2),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10),
          child: Row(
            children: [
              Icon(icon,
                  size: 22,
                  color: selected ? AppColors.brandBlack : AppColors.ink700),
              const SizedBox(width: 8),
              Expanded(
                child: Text(label,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        fontSize: 13,
                        height: 1.25,
                        fontWeight: FontWeight.w700,
                        color:
                            selected ? AppColors.brandBlack : AppColors.ink900)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
