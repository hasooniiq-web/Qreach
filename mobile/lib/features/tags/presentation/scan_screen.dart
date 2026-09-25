import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../core/network/api_client.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/providers.dart';
import '../../../l10n/app_localizations.dart';
import '../../vehicles/presentation/vehicle_controller.dart';
import '../data/tag_repository.dart';

class ScanScreen extends ConsumerStatefulWidget {
  const ScanScreen({super.key, required this.vehicleId});

  final String vehicleId;

  @override
  ConsumerState<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends ConsumerState<ScanScreen> {
  final _controller = MobileScannerController();
  bool _processing = false;
  String? _error;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_processing) return;
    if (capture.barcodes.isEmpty) return;
    final rawValue = capture.barcodes.first.rawValue;
    if (rawValue == null) return;

    setState(() {
      _processing = true;
      _error = null;
    });
    await _controller.stop();

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
      if (mounted) context.pop();
    } catch (e) {
      final apiError = asApiException(e);
      setState(() {
        _error = _messageForCode(apiError.code) ?? apiError.message;
        _processing = false;
      });
      await _controller.start();
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
          elevation: 0),
      body: Stack(
        children: [
          MobileScanner(controller: _controller, onDetect: _onDetect),
          const _ScanOverlay(),
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
                    l10n.scanTagInstructions,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                        color: AppColors.brandWhite, fontSize: 15),
                  ),
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
              style: const TextStyle(color: AppColors.ink500),
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
