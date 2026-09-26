import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../core/network/api_client.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/providers.dart';
import '../../../l10n/app_localizations.dart';
import '../../vehicles/presentation/vehicle_controller.dart';
import '../data/nfc_reader.dart';
import '../data/tag_repository.dart';

class ScanScreen extends ConsumerStatefulWidget {
  const ScanScreen({super.key, required this.vehicleId});

  final String vehicleId;

  @override
  ConsumerState<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends ConsumerState<ScanScreen> {
  final _controller = MobileScannerController();
  final _nfc = const NfcReader();
  bool _processing = false;
  bool _nfcMode = false;
  String? _error;

  @override
  void dispose() {
    _nfc.stop();
    _controller.dispose();
    super.dispose();
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_processing) return;
    if (capture.barcodes.isEmpty) return;
    final rawValue = capture.barcodes.first.rawValue;
    if (rawValue == null) return;
    await _activate(rawValue);
  }

  Future<void> _setMode({required bool nfc}) async {
    if (_nfcMode == nfc || _processing) return;
    final l10n = AppLocalizations.of(context)!;

    setState(() {
      _nfcMode = nfc;
      _error = null;
    });

    if (!nfc) {
      await _nfc.stop();
      try {
        await _controller.start();
      } catch (_) {}
      return;
    }

    try {
      await _controller.stop();
    } catch (_) {}
    final state = await _nfc.state();
    if (!mounted) return;
    if (state != NfcState.ready) {
      setState(() => _error =
          state == NfcState.disabled ? l10n.nfcDisabled : l10n.nfcUnavailable);
      return;
    }
    await _startNfc();
  }

  Future<void> _startNfc() async {
    final l10n = AppLocalizations.of(context)!;
    await _nfc.start(onLink: (link) {
      if (!mounted) return;
      if (link == null) {
        setState(() => _error = l10n.nfcNoLink);
        return;
      }
      _activate(link);
    });
  }

  Future<void> _enterManually() async {
    final l10n = AppLocalizations.of(context)!;
    final input = TextEditingController();
    final value = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n.scanManualTitle),
        content: TextField(
          controller: input,
          autofocus: true,
          textDirection: TextDirection.ltr,
          decoration: InputDecoration(hintText: l10n.scanManualHint),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: Text(l10n.cancel)),
          TextButton(
              onPressed: () => Navigator.of(context).pop(input.text.trim()),
              child: Text(l10n.scanManualButton)),
        ],
      ),
    );
    input.dispose();
    if (value != null && value.isNotEmpty) await _activate(value);
  }

  Future<void> _activate(String rawValue) async {
    if (_processing) return;
    setState(() {
      _processing = true;
      _error = null;
    });
    try {
      await _controller.stop();
    } catch (_) {}
    await _nfc.stop();

    final token = TagRepository.extractToken(rawValue);

    try {
      await ref
          .read(tagRepositoryProvider)
          .activate(token: token, vehicleId: widget.vehicleId);
      ref.invalidate(vehicleDetailProvider(widget.vehicleId));

      if (!mounted) return;
      await showDialog<void>(
        context: context,
        barrierDismissible: false,
        builder: (_) => const _ActivationSuccessDialog(),
      );
      ref.read(vehicleListControllerProvider.notifier).refresh();
      if (mounted) context.pop();
    } catch (e) {
      final apiError = asApiException(e);
      setState(() {
        _error = _messageForCode(apiError.code) ?? apiError.message;
        _processing = false;
      });
      if (_nfcMode) {
        await _startNfc();
      } else {
        try {
          await _controller.start();
        } catch (_) {}
      }
    }
  }

  String? _messageForCode(String code) {
    final l10n = AppLocalizations.of(context)!;
    return switch (code) {
      'TAG_ALREADY_ACTIVATED' => l10n.tagAlreadyActivated,
      'TAG_REVOKED' => l10n.tagRevoked,
      'TAG_NOT_FOUND' => l10n.tagNotFound,
      _ => null,
    };
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
          ]),
      body: Stack(
        children: [
          if (_nfcMode)
            const _NfcPrompt()
          else ...[
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
            const _ScanOverlay(),
          ],
          Positioned(
            left: 24,
            right: 24,
            bottom: 48,
            child: Column(
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.6),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    _nfcMode ? l10n.nfcHoldPhone : l10n.scanTagInstructions,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                        color: AppColors.brandWhite, fontSize: 15),
                  ),
                ),
                const SizedBox(height: 12),
                SegmentedButton<bool>(
                  showSelectedIcon: false,
                  style: SegmentedButton.styleFrom(
                    backgroundColor: Colors.black.withValues(alpha: 0.6),
                    foregroundColor: AppColors.brandWhite,
                    selectedBackgroundColor: AppColors.brandYellow,
                    selectedForegroundColor: AppColors.brandBlack,
                  ),
                  segments: [
                    ButtonSegment(
                        value: false,
                        icon: const Icon(Icons.qr_code_scanner),
                        label: Text(l10n.scanModeQr)),
                    ButtonSegment(
                        value: true,
                        icon: const Icon(Icons.nfc),
                        label: Text(l10n.scanModeNfc)),
                  ],
                  selected: {_nfcMode},
                  onSelectionChanged: (v) => _setMode(nfc: v.first),
                ),
                const SizedBox(height: 4),
                TextButton.icon(
                  onPressed: _processing ? null : _enterManually,
                  icon: const Icon(Icons.keyboard_alt_outlined,
                      color: AppColors.brandYellow),
                  label: Text(l10n.scanManualTitle,
                      style: const TextStyle(color: AppColors.brandYellow)),
                ),
                if (_processing) ...[
                  const SizedBox(height: 16),
                  const CircularProgressIndicator(color: AppColors.brandYellow),
                ],
                if (_error != null) ...[
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: AppColors.statusDanger.withValues(alpha: 0.9),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(_error!,
                        style: const TextStyle(color: AppColors.brandWhite),
                        textAlign: TextAlign.center),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Dims everything outside a square viewfinder and draws yellow corner
/// brackets — clearer than a plain outlined box about where to aim.
class _ScanOverlay extends StatelessWidget {
  const _ScanOverlay();

  static const double _size = 250;

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(
      child: CustomPaint(
        size: Size.infinite,
        painter: _ViewfinderPainter(),
        child: const Center(child: SizedBox(width: _size, height: _size)),
      ),
    );
  }
}

class _ViewfinderPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    const side = _ScanOverlay._size;
    final rect = Rect.fromCenter(
        center: size.center(Offset.zero), width: side, height: side);
    final rrect = RRect.fromRectAndRadius(rect, const Radius.circular(20));

    final scrim = Path()
      ..addRect(Offset.zero & size)
      ..addRRect(rrect)
      ..fillType = PathFillType.evenOdd;
    canvas.drawPath(
        scrim, Paint()..color = Colors.black.withValues(alpha: 0.55));

    final bracket = Paint()
      ..color = AppColors.brandYellow
      ..style = PaintingStyle.stroke
      ..strokeWidth = 5
      ..strokeCap = StrokeCap.round;
    const len = 34.0;
    final r = rect;
    for (final corner in [
      (r.topLeft, const Offset(1, 0), const Offset(0, 1)),
      (r.topRight, const Offset(-1, 0), const Offset(0, 1)),
      (r.bottomLeft, const Offset(1, 0), const Offset(0, -1)),
      (r.bottomRight, const Offset(-1, 0), const Offset(0, -1)),
    ]) {
      final p = corner.$1;
      canvas.drawLine(p, p + corner.$2 * len, bracket);
      canvas.drawLine(p, p + corner.$3 * len, bracket);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// Confirms a successful tag activation with a short scale-in check — the
/// one moment in the flow worth a little motion. Fast (450ms), one element,
/// and no animation when the platform asks for reduced motion.
class _ActivationSuccessDialog extends StatelessWidget {
  const _ActivationSuccessDialog();

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final reduceMotion = MediaQuery.of(context).disableAnimations;

    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const SizedBox(height: 8),
          TweenAnimationBuilder<double>(
            tween: Tween(begin: reduceMotion ? 1 : 0, end: 1),
            duration: const Duration(milliseconds: 450),
            curve: Curves.easeOutBack,
            builder: (context, scale, child) =>
                Transform.scale(scale: scale, child: child),
            child: Container(
              width: 72,
              height: 72,
              decoration: const BoxDecoration(
                  color: AppColors.brandYellow, shape: BoxShape.circle),
              child: const Icon(Icons.check,
                  size: 40, color: AppColors.brandBlack),
            ),
          ),
          const SizedBox(height: 20),
          Text(l10n.activationSuccess,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
              textAlign: TextAlign.center),
          const SizedBox(height: 6),
          Text(l10n.activationSuccessSubtitle,
              style: TextStyle(color: AppColors.ink500),
              textAlign: TextAlign.center),
        ],
      ),
      actionsAlignment: MainAxisAlignment.center,
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: Text(l10n.done),
        ),
      ],
    );
  }
}

/// What the NFC mode shows instead of the camera: an unmistakable "tap the
/// tag on the back of the phone" prompt.
class _NfcPrompt extends StatelessWidget {
  const _NfcPrompt();

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.brandYellow, width: 4),
              ),
              child:
                  const Icon(Icons.nfc, size: 56, color: AppColors.brandYellow),
            ),
            const SizedBox(height: 24),
            Text(l10n.nfcHoldPhone,
                textAlign: TextAlign.center,
                style: const TextStyle(
                    color: AppColors.brandWhite,
                    fontSize: 18,
                    fontWeight: FontWeight.w700)),
          ],
        ),
      ),
    );
  }
}
