import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:nfc_manager/nfc_manager.dart';
import 'package:nfc_manager/nfc_manager_android.dart';

enum NfcState { ready, disabled, unsupported }

/// Reads the Qreach link stored on an NFC tag (an NDEF URL record such as
/// `https://host/t/TOKEN`). Android only — on other platforms NFC is simply
/// reported as unsupported and the QR camera remains the way to link a code.
class NfcReader {
  const NfcReader();

  static bool get _platformOk =>
      !kIsWeb && defaultTargetPlatform == TargetPlatform.android;

  Future<NfcState> state() async {
    if (!_platformOk) return NfcState.unsupported;
    try {
      return switch (await NfcManager.instance.checkAvailability()) {
        NfcAvailability.enabled => NfcState.ready,
        NfcAvailability.disabled => NfcState.disabled,
        NfcAvailability.unsupported => NfcState.unsupported,
      };
    } catch (_) {
      return NfcState.unsupported;
    }
  }

  /// Waits for one tag. [onLink] gets the URL/text found on it, or null when
  /// the tag has no readable link.
  Future<void> start({required void Function(String? link) onLink}) {
    return NfcManager.instance.startSession(
      pollingOptions: {
        NfcPollingOption.iso14443,
        NfcPollingOption.iso15693,
      },
      onDiscovered: (tag) async {
        final message = NdefAndroid.from(tag)?.cachedNdefMessage;
        String? link;
        for (final record in message?.records ?? const []) {
          link = linkFromPayload(record.type, record.payload);
          if (link != null) break;
        }
        onLink(link);
      },
    );
  }

  Future<void> stop() async {
    try {
      await NfcManager.instance.stopSession();
    } catch (_) {}
  }

  static const _uriPrefixes = {
    0x01: 'http://www.',
    0x02: 'https://www.',
    0x03: 'http://',
    0x04: 'https://',
  };

  /// Decodes an NDEF well-known URI ("U") or text ("T") record into a string;
  /// anything else yields null. Public so it can be unit-tested.
  @visibleForTesting
  static String? linkFromPayload(Uint8List type, Uint8List payload) {
    if (payload.isEmpty || type.length != 1) return null;

    if (type[0] == 0x55) {
      // 'U'
      final prefix = _uriPrefixes[payload[0]] ?? '';
      return prefix + utf8.decode(payload.sublist(1), allowMalformed: true);
    }
    if (type[0] == 0x54) {
      // 'T': status byte (low 6 bits = language code length), then text.
      final langLength = payload[0] & 0x3f;
      if (payload.length <= 1 + langLength) return null;
      return utf8.decode(payload.sublist(1 + langLength), allowMalformed: true);
    }
    return null;
  }
}
