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
      final l10n = AppLocalizations.of(context)!;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(l10n.activationSuccess)));
      context.pop();
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
          _ScanOverlay(),
          Positioned(
            left: 24,
            right: 24,
            bottom: 48,
            child: Column(
              children: [
                Text(
                  l10n.scanTagInstructions,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                      color: AppColors.brandWhite, fontSize: 15),
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

class _ScanOverlay extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Center(
      child: Container(
        width: 240,
        height: 240,
        decoration: BoxDecoration(
          border: Border.all(color: AppColors.brandYellow, width: 3),
          borderRadius: BorderRadius.circular(16),
        ),
      ),
    );
  }
}
