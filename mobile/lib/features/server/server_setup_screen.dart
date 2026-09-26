import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/providers.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_spacing.dart';
import '../../l10n/app_localizations.dart';
import '../../shared/widgets/app_button.dart';

/// Turns what the user typed into an API base URL, or null if it is not a
/// usable address. Accepts `192.168.1.10:8000`, `myserver.com`, or a full
/// `http(s)://host:port` (an existing `/api/v1` suffix is tolerated).
/// Without a scheme, an address with a port or a bare IP/localhost is plain
/// http (a dev server on the LAN); anything else is https.
String? parseServerAddress(String input) {
  var text = input.trim();
  if (text.isEmpty || text.contains(RegExp(r'\s'))) return null;

  String scheme;
  if (text.startsWith('http://') || text.startsWith('https://')) {
    scheme = text.startsWith('https://') ? 'https' : 'http';
    text = text.substring(scheme.length + 3);
  } else {
    final hostOnly = text.split('/').first;
    final isIp = RegExp(r'^\d{1,3}(\.\d{1,3}){3}(:\d+)?$').hasMatch(hostOnly);
    scheme =
        (hostOnly.contains(':') || isIp || hostOnly.startsWith('localhost'))
            ? 'http'
            : 'https';
  }

  final slash = text.indexOf('/');
  final authority = slash == -1 ? text : text.substring(0, slash);
  final uri = Uri.tryParse('$scheme://$authority');
  if (uri == null || uri.host.isEmpty) return null;
  if (uri.hasPort && (uri.port < 1 || uri.port > 65535)) return null;

  return '$scheme://${uri.host}${uri.hasPort ? ':${uri.port}' : ''}/api/v1';
}

/// Where the app talks to: shown before login too, so a phone can be pointed
/// at a different server (LAN dev machine, staging, production) without
/// rebuilding the app.
class ServerSetupScreen extends ConsumerStatefulWidget {
  const ServerSetupScreen({super.key});

  @override
  ConsumerState<ServerSetupScreen> createState() => _ServerSetupScreenState();
}

class _ServerSetupScreenState extends ConsumerState<ServerSetupScreen> {
  final _controller = TextEditingController();
  bool _busy = false;
  String? _message;
  bool _ok = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    String? saved;
    try {
      saved = await ref.read(secureStorageProvider).readServerBaseUrl();
    } catch (_) {
      // Storage unavailable: fall back to the built-in address.
    }
    final current = saved ?? apiBaseUrl;
    if (!mounted) return;
    _controller.text = current
        .replaceFirst(RegExp(r'/api/v1/?$'), '')
        .replaceFirst('http://', '');
    setState(() {});
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  String? _validated(AppLocalizations l10n) {
    final base = parseServerAddress(_controller.text);
    if (base == null) {
      setState(() {
        _ok = false;
        _message = l10n.serverInvalid;
      });
      return null;
    }
    if (kReleaseMode && base.startsWith('http://')) {
      setState(() {
        _ok = false;
        _message = l10n.serverHttpsOnly;
      });
      return null;
    }
    return base;
  }

  Future<void> _test() async {
    final l10n = AppLocalizations.of(context)!;
    final base = _validated(l10n);
    if (base == null) return;

    setState(() {
      _busy = true;
      _message = null;
    });
    try {
      final origin = base.replaceFirst(RegExp(r'/api/v1$'), '');
      final response = await Dio(BaseOptions(
        connectTimeout: const Duration(seconds: 6),
        receiveTimeout: const Duration(seconds: 6),
        validateStatus: (_) => true,
      )).get<dynamic>('$origin/up');
      final ok = response.statusCode == 200;
      setState(() {
        _ok = ok;
        _message = ok ? l10n.serverOk : l10n.serverFail;
      });
    } catch (_) {
      setState(() {
        _ok = false;
        _message = l10n.serverFail;
      });
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _save() async {
    final l10n = AppLocalizations.of(context)!;
    final base = _validated(l10n);
    if (base == null) return;

    await ref.read(secureStorageProvider).saveServerBaseUrl(base);
    if (!mounted) return;
    setState(() {
      _ok = true;
      _message = l10n.serverSaved;
    });
  }

  Future<void> _reset() async {
    final l10n = AppLocalizations.of(context)!;
    await ref.read(secureStorageProvider).clearServerBaseUrl();
    if (!mounted) return;
    _controller.text = apiBaseUrl
        .replaceFirst(RegExp(r'/api/v1/?$'), '')
        .replaceFirst('http://', '');
    setState(() {
      _ok = true;
      _message = l10n.serverResetDone;
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.serverSetup)),
      body: ListView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          Text(l10n.serverSetupHelp,
              style: TextStyle(color: AppColors.ink500, height: 1.5)),
          const SizedBox(height: AppSpacing.lg),
          TextField(
            controller: _controller,
            keyboardType: TextInputType.url,
            textDirection: TextDirection.ltr,
            autocorrect: false,
            decoration: InputDecoration(
              labelText: l10n.serverAddress,
              hintText: '192.168.1.10:8000',
              prefixIcon: const Icon(Icons.dns_outlined),
            ),
            onChanged: (_) => setState(() => _message = null),
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            parseServerAddress(_controller.text) ?? '',
            textDirection: TextDirection.ltr,
            style: TextStyle(color: AppColors.ink400, fontSize: 12),
          ),
          if (_message != null) ...[
            const SizedBox(height: AppSpacing.md),
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: (_ok ? AppColors.statusSuccess : AppColors.statusDanger)
                    .withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                children: [
                  Icon(_ok ? Icons.check_circle : Icons.error_outline,
                      color: _ok
                          ? AppColors.statusSuccess
                          : AppColors.statusDanger),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(child: Text(_message!)),
                ],
              ),
            ),
          ],
          const SizedBox(height: AppSpacing.xl),
          AppButton(
            label: l10n.serverTest,
            icon: Icons.wifi_tethering_rounded,
            style: AppButtonStyle.secondary,
            loading: _busy,
            onPressed: _busy ? null : _test,
          ),
          const SizedBox(height: AppSpacing.md),
          AppButton(label: l10n.save, onPressed: _busy ? null : _save),
          const SizedBox(height: AppSpacing.md),
          AppButton(
            label: l10n.serverReset,
            style: AppButtonStyle.ghost,
            onPressed: _busy ? null : _reset,
          ),
        ],
      ),
    );
  }
}
